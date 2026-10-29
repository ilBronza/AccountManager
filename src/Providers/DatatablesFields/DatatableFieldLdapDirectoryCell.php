<?php

namespace IlBronza\AccountManager\Providers\DatatablesFields;

use IlBronza\Datatables\DatatablesFields\DatatableFieldFlat;

class DatatableFieldLdapDirectoryCell extends DatatableFieldFlat
{
    public $requireElement = true;
    public string $cell;

    public function transformValue($entry)
    {
        return view('accountmanager::ldap.cells.'.$this->cell, [
            'entry' => $entry,
            'localUsers' => $this->table->getVariable('localUsers'),
            'linkedUsers' => $this->table->getVariable('linkedUsers'),
        ])->render();
    }

    public function getColumnDefs()
    {
        $definitions = parent::getColumnDefs();
        if ($this->cell === 'association') $definitions['searchable'] = false;

        return $definitions;
    }

    public function getCustomColumnDefSingleResultExport()
    {
        return $this->cell === 'association' ? "return '';" : parent::getCustomColumnDefSingleResultExport();
    }
}
