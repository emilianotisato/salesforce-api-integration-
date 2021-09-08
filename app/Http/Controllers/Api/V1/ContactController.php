<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;

class ContactController extends Controller
{
    /**
     * Contact index paginated
     *
     * @param \Illuminate\Http\Request $reques
     * @return void
     */
    public function index(Request $reques)
    {
        return ContactResource::collection(Contact::paginate(config('system.pagination_amount')));
    }

    /**
     * Store a new contact
     *
     * @param \App\Http\Requests\ContactRequest $request
     * @return void
     */
    public function store(ContactRequest $request)
    {
        return Contact::create($request->all());
    }

    /**
     * Update a contact
     *
     * @param \App\Models\Contact $contact
     * @param \App\Http\Requests\ContactRequest $request
     * @return void
     */
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

    /**
     * Show a contact info
     *
     * @param \App\Models\Contact $contact
     * @return void
     */
    public function show(Contact $contact)
    {
        return ContactResource::make($contact);
    }

    /**
     * Delete a contact
     *
     * @param \App\Models\Contact $contact
     * @return void
     */
    public function delete(Contact $contact)
    {
        return $contact->delete();
    }
}
