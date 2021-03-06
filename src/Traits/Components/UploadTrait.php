<?php

namespace Traits\Components;

use Models\Upload;

trait UploadTrait
{
    protected $uploads_directory = 'files';

    /**
     * Restituisce il percorso per il salvataggio degli upload.
     *
     * @return string
     */
    public function getUploadDirectoryAttribute()
    {
        $directory = $this->directory ?: 'common';

        $result = $this->uploads_directory.'/'.$directory;
        directory($result);

        return $result;
    }

    public function uploads($id_record)
    {
        return $this->hasMany(Upload::class, $this->component_identifier)->where('id_record', $id_record)->get();
    }
}
