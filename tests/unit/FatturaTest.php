<?php

use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo;

class FatturaTest extends \Codeception\Test\Unit
{
    public function testCreate()
    {
        $data = date('Y-m-d H:i:s');

        $id_anagrafica = 1;
        $id_tipo = 2;
        $id_segment = 1;

        $anagrafica = Anagrafica::find($id_anagrafica);
        $tipo = Tipo::find($id_tipo);

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_segment);

        $this->assertEquals($fattura->idanagrafica, 1);
        $this->assertEquals($fattura->id_tipo_documento, 2);
        $this->assertEquals($fattura->id_segment, 1);
        $this->assertEquals($fattura->data, $data);
    }
}
