<?php

namespace App\Http\Requests;

use App\Models\Membership;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreMembershipRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('membership_create');
    }

    public function rules()
    {
        return [
            'fullname' => [
                'string',
                'required',
            ],
            'address' => [
                'string',
                'required',
            ],
            'phone' => [
                'string',
                'required',
            ],
        ];
    }
}
