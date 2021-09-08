<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;

class ContactController extends Controller
{
    public function index(Request $reques)
    {
        return ContactResource::collection(Contact::paginate(config('system.pagination_amount')));
    }

    public function store(ContactRequest $request)
    {
        return Contact::create($request->all());
    }

    public function update(Contact $contact, ContactRequest $request)
    {
        return $contact->update($request->only(
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'lead_source',
        ));
    }

    public function show(Contact $contact)
    {
        return ContactResource::make($contact);
    }
}
