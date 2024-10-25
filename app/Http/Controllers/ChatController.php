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

        // Проверяем, является ли пользователь продавцом этого товара
        $isSeller = $store->customer_id == $user->id;

        if ($isSeller) {
            // Продавец видит все переписки с покупателями
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
            // Покупатель видит только свои сообщения и сообщения от продавца
            $chats = Chat::where('ec_product_id', $productId)
                         ->where(function ($query) use ($user) {
                             $query->where('user_id', $user->id)
                                   ->orWhere('store_id', $user->id);
                         })
                         ->paginate(10);

            return view('chat.index', compact('chats', 'product', 'store', 'user'));
        }
    }
    
    public function sendMessage(Request $request, $productId, $storeId)
    {
        $user = Auth::guard('customer')->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'message' => 'required|string|max:1000',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:2048'
        ]);

        $chat = new Chat();
        $chat->ec_product_id = $productId;
        $chat->message = $request->message;
        $store = Store::findOrFail($storeId);

        if ($user) {
            $chat->user_id = $user->id;
            $chat->store_id = $store->id; // Связывание с store_id
        }

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('chat_files', 'public');
            $chat->file_path = $path; // Сохраняем путь к файлу
        }

        Log::info('Chat before save', ['chat' => $chat]);

        $chat->save();

        Log::info('Chat after save', ['chat' => $chat]);

        return redirect()->route('chat.index', ['productId' => $productId, 'storeId' => $storeId])
                         ->with('success', 'Message sent!');
    }

    public function viewMessage($productId, $storeId, $userId)
    {
        $user = Auth::guard('customer')->user();
    
        if (!$user) {
            return redirect()->route('login');
        }
    
        $store = Store::findOrFail($storeId);
        $product = Product::findOrFail($productId);
    
        // Проверяем, является ли пользователь продавцом этого товара
        $isSeller = $store->customer_id == $user->id;
    
        if ($isSeller) {
            // Продавец видит переписку с конкретным покупателем
            $chats = Chat::where('ec_product_id', $productId)
                         ->where('user_id', $userId)
                         ->orWhere('store_id', $store->id)
                         ->orderBy('created_at', 'desc')
                         ->paginate(10);
    
            return view('chat.view_message', compact('chats', 'product', 'store', 'user'));
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
