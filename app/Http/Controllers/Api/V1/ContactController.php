<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;

class ContactController extends Controller
{
    public function index(Request $reques)
    {
        return ContactResource::collection(Contact::paginate(config('system.pagination_amount')));
    }
}
