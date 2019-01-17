<?php

/**
 * Classe per la gestione delle utenze.
 *
 * @since 2.4
 */
class Import
{
    /** @var int Identificativo del modulo corrente */
    protected static $imports;

    /**
     * Restituisce tutte le informazioni di tutti i moduli installati.
     *
     * @return array
     */
    public static function getImports()
    {
        if (empty(self::$imports)) {
            $modules = Modules::getModules();

            $database = database();

            $results = [];
            foreach ($modules as $module) {
                $file = DOCROOT.'/modules/'.$module['directory'].'|custom|/import.php';

                $original_file = str_replace('|custom|', '', $file);
                $custom_file = str_replace('|custom|', '/custom', $file);

                if (file_exists($custom_file) || file_exists($original_file)) {
                    $files = Uploads::get([
                        'id_module' => Modules::get('Import')['id'],
                        'id_record' => $module['id'],
                    ]);

                    $results[$module['id']] = array_merge($module->toArray(), [
                        'import' => file_exists($custom_file) ? $custom_file : $original_file,
                        'files' => array_reverse($files),
                    ]);
                }
            }

            self::$imports = $results;
        }

        return self::$imports;
    }

    /**
     * Restituisce le informazioni relative a un singolo modulo specificato.
     *
     * @param string|int $module
     *
     * @return array
     */
    public static function get($module)
    {
        $module = Modules::get($module)['id'];

        return self::getImports()[$module];
    }

    /**
     * Restituisce l'elenco dei campi previsti dal modulo.
     *
     * @param string|int $module
     *
     * @return array
     */
    public static function getFields($module)
    {
        $import = self::get($module);

        ob_start();
        $fields = require $import['import'];
        ob_end_clean();

        // Impostazione automatica dei nomi "ufficiali" dei campi
        foreach ($fields as $key => $value) {
            if (!isset($value['names'])) {
                $names = [
                    $value['field'],
                    $value['label'],
                ];
            } else {
                $names = $value['names'];
            }

            // Impostazione dei nomi in minuscolo
            foreach ($names as $k => $v) {
                $names[$k] = str_to_lower($v);
            }

            $fields[$key]['names'] = $names;
        }

        return $fields;
    }

    /**
     * Restituisce i contenuti del file CSV indicato.
     *
     * @param string|int $module
     * @param int        $file_id
     * @param array      $options
     *
     * @return array
     */
    public static function getFile($module, $file_id, $options = [])
    {
        $import = self::get($module);

        $ids = array_column($import['files'], 'id');
        $find = array_search($file_id, $ids);

        if ($find == -1) {
            return [];
        }

        $file = DOCROOT.'/files/'.Modules::get('Import')['directory'].'/'.$import['files'][$find]['filename'];

        // Impostazione automatica per i caratteri di fine riga
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        // Gestione del file CSV
        $csv = League\Csv\Reader::createFromPath($file, 'r');
        $csv->setDelimiter(';');

        // Ignora la prima riga
        $offset = 0;
        if (!empty($options['headers'])) {
            ++$offset;
        }
        $rows = $csv->setOffset($offset);

        // Limite di righe
        if (!empty($options['limit'])) {
            $rows = $rows->setLimit($options['limit']);
        }

        // Lettura
        $rows = $rows->fetchAll();

        return $rows;
    }

    public static function createExample($module, array $content)
    {
        $module = Modules::get($module);
        $upload_dir = Uploads::getDirectory($module->id, null);

        $filename = $upload_dir.'/example-'.strtolower($module->title).'.csv';

        $file = fopen(DOCROOT.'/'.$filename, 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($content as $row) {
            fputcsv($file, $row, ';');
        }

        fclose($file);

        return $filename;
    }
}
