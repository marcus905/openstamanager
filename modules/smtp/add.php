<?php

?><form action="<?php pathFor('module-add-save', [
        'module_id' => $module_id,
        'reference_id' => $reference_id,
    ]); ?>" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

    <div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "<?php echo tr('Nome account'); ?>", "name": "name", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Nome visualizzato'); ?>", "name": "from_name" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "email", "label": "<?php echo tr('Email mittente'); ?>", "name": "from_address", "required": 1 ]}
		</div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
        </div>
    </div>
</form>
