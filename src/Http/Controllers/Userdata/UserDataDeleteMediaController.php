<?php

namespace IlBronza\AccountManager\Http\Controllers\Userdata;

use IlBronza\CRUD\Models\Media;

class UserDataDeleteMediaController extends BaseUserdataPackageController
{
    public $allowedMethods = ['deleteMedia'];

    public function deleteMedia(Media $media)
    {
        $userdata = $this->getUserdata();

        if(! $userdata->is($media->model))
            return [
                'success' => false
            ];

        $media->delete();

        return [
            'success' => true
        ];
    }



}