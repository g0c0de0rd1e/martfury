<?php

namespace Botble\Marketplace\Http\Controllers\Fronts;

use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Marketplace\Facades\MarketplaceHelper;
use Botble\Marketplace\Models\Message;
use Botble\Marketplace\Tables\MessageTable;

class MessageController extends BaseController
{
    public function index(MessageTable $messageTable)
    {
        if (! MarketplaceHelper::isEnabledMessagingSystem()) {
            abort(404);
        }

        $this->pageTitle(__('Messages'));

        return $messageTable->renderTable();
    }

    public function show(string $id)
    {
        if (! MarketplaceHelper::isEnabledMessagingSystem()) {
            abort(404);
        }

        $message = Message::query()
            ->where('store_id', auth('customer')->user()->store->id)
            ->with(['store', 'customer'])
            ->findOrFail($id);

        $this->pageTitle(__('Viewing message #:id', ['id' => $message->getKey()]));

        return MarketplaceHelper::view('vendor-dashboard.messages.show', compact('message'));
    }
    
    public function send(Request $request) {
        if (!MarketplaceHelper::isEnabledMessagingSystem()) {
            abort(404);
        }
    
        $validatedData = $request->validate([
            'customer_id' =>    'required|exists:customers,id',
            'name'        =>    'required|string|max:255',
            'email'       =>    'required|string|email|max:255',
            'content'     =>    'required|string|max:1000',
        ]);
    
        $message = new Message();
        $message->store_id = auth('customer')->user()->store->id;
        $message->customer_id = $validatedData['customer_id'];
        $message->name = $validatedData['name'];
        $message->email = $validatedData['email'];
        $message->content = $validatedData['content'];
        $message->save();
    
        return response()->json(['status' => 'Message sent successfully'], 200);
    }
    
    public function destroy(string $id)
    {
        if (! MarketplaceHelper::isEnabledMessagingSystem()) {
            abort(404);
        }

        $message = Message::query()
            ->where('store_id', auth('customer')->user()->store->id)
            ->findOrFail($id);

        return DeleteResourceAction::make($message);
    }
}
