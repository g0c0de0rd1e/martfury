<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Botble\Ecommerce\Models\Product;
use Botble\Marketplace\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function index($productId, $storeId)
    {
        $user = Auth::guard('customer')->user();
        if (!$user) {
            return redirect()->route('login');
        }
        $store = Store::findOrFail($storeId);
        $product = Product::findOrFail($productId);

        $isSeller = $store->customer_id == $user->id;
        if ($isSeller) {
            $customers = Chat::where('ec_product_id', $productId)
                             ->select('user_id')
                             ->distinct()
                             ->with('user')
                             ->get()
                             ->map(function($customer) use ($productId, $storeId) {
                                 $customer->messages_count = Chat::where('ec_product_id', $productId)
                                                                 ->where('user_id', $customer->user_id)
                                                                 ->where('store_id', $storeId)
                                                                 ->count();
                                 $customer->last_message = Chat::where('ec_product_id', $productId)
                                                               ->where('user_id', $customer->user_id)
                                                               ->where('store_id', $storeId)
                                                               ->latest('created_at')
                                                               ->first();
                                 return $customer;
                             });
            return view('chat.seller_index', compact('customers', 'product', 'store', 'user'));
        } else {
            $chats = Chat::where('ec_product_id', $productId)
                         ->where(function ($query) use ($user, $store) {
                             $query->where('user_id', $user->id)
                                   ->orWhere(function($subQuery) use ($store) {
                                       $subQuery->where('user_id', $store->customer_id)
                                                ->where('store_id', $store->id);
                                   });
                         })
                         ->paginate(10);
            return view('chat.index', compact('chats', 'product', 'store', 'user'));
        }
    }

    public function sendMessage(Request $request, $productId, $storeId, $userId = null)
    {
        $user = Auth::guard('customer')->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $store = Store::findOrFail($storeId);

        $request->validate([
            'message' => 'required|string|max:1000',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:2048'
        ]);

        $chat = new Chat();
        $chat->ec_product_id = $productId;
        $chat->message = $request->message;
        $chat->store_id = $storeId;
        $chat->sender_id = $user->id; // добавляем sender_id

        if ($user->id == $store->customer_id) {
            // Сообщение от продавца к конкретному покупателю
            $chat->user_id = $userId;
        } else {
            // Сообщение от покупателя к продавцу
            $chat->user_id = $user->id;
        }

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('chat_files', 'public');
            $chat->file_path = $path;
        }

        Log::info('Chat before save', ['chat' => $chat]);

        // Проверяем перед сохранением
        if (is_null($chat->user_id)) {
            return redirect()->back()->withErrors('User ID cannot be null.');
        }

        $chat->save();
        Log::info('Chat after save', ['chat' => $chat]);

        if ($user->id == $store->customer_id) {
            // Оставаться на той же странице при отправке сообщения продавцом
            return redirect()->route('chat.viewMessage', ['productId' => $productId, 'storeId' => $storeId, 'userId' => $userId])
                            ->with('success', 'Message sent!');
        } else {
            // Оставаться на той же странице при отправке сообщения покупателем
            return redirect()->route('chat.index', ['productId' => $productId, 'storeId' => $storeId])
                            ->with('success', 'Message sent!');
        }
    }

    public function viewMessage($productId, $storeId, $userId)
    {
        $user = Auth::guard('customer')->user();
        if (!$user) {
            return redirect()->route('login');
        }
        $store = Store::findOrFail($storeId);
        $product = Product::findOrFail($productId);

        $isSeller = $store->customer_id == $user->id;
        if ($isSeller) {
            $chats = Chat::where('ec_product_id', $productId)
                         ->where('store_id', $storeId)
                         ->where('user_id', $userId)
                         ->orderBy('created_at', 'desc')
                         ->paginate(10);
            return view('chat.view_message', compact('chats', 'product', 'store', 'user', 'userId'));
        } else {
            return redirect()->route('chat.index', ['productId' => $productId, 'storeId' => $storeId]);
        }
    }

    public function show(Chat $chat)
    {
        $this->authorize('view', $chat); // Проверяем права на просмотр чата
        return view('chat.show', compact('chat'));
    }

    public function destroy(Chat $chat)
    {
        $this->authorize('delete', $chat); // Проверяем права на удаление чата
        if ($chat->file_path) {
            Storage::disk('public')->delete($chat->file_path); // Удаляем прикрепленный файл
        }
        $chat->delete();
        return redirect()->route('chat.index', ['productId' => $chat->ec_product_id, 'storeId' => $chat->store_id])->with('success', 'Message deleted!');
    }
}
