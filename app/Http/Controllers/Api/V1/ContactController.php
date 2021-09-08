<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;
use App\Services\SalesforceApi;

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

    /**
     * Sync contacts with Salesforce
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\SalesforceApi $salesforce
     * @return void
     */
    public function sync(Request $request, SalesforceApi $salesforce)
    {
        // TODO we are not validating here becouse we trust the data comming from the API, should we add validation?

        $salesforce->module('contacts')->all()->each(function ($sfContact) {
            $contact = Contact::where('salesforce_id', $sfContact->id)->first();
            if($sfContact->is_deleted && $contact) {
                $contact->delete();
                return;
            }

            if($contact) {
                $contact->update((array) $sfContact);
            } else {
                // Normalize salesforce ID
                $data = array_merge((array) $sfContact, ['salesforce_id' => $sfContact->id]);

                Contact::create($data);
            }
        });
    }
}
