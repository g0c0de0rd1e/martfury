<div class="ps-section--chats">
    <div class="container">
        <div class="ps-section__header">
            <h1>{{ __('Чаты с покупателями') }}</h1>
        </div>
        <div class="ps-section__content">
            @if ($customers->count())
                <div class="chat-list">
                    @foreach ($customers as $customer)
                    <div class="chat-item {{ $loop->first ? 'active' : '' }}" 
                        data-user-id="{{ $customer->user_id }}"
                        data-product-id="{{ $product->id }}"
                        data-store-id="{{ $store->id }}">
                        <div class="chat-avatar">
                            <img src="{{ asset($customer->user->avatar ?? '/images/default-avatar.png') }}" alt="Аватар пользователя" class="rounded-circle">
                        </div>
                        <div class="chat-info">
                            <h4>{{ $customer->user->name }}</h4>
                            <p>{{ $customer->last_message->text ?? 'Нет сообщений' }}</p>
                            <span class="time">{{ $customer->last_message->created_at->format('d M, Y H:i') ?? 'Нет данных' }}</span>
                            <span class="badge badge-pill badge-secondary ml-auto">{{ $customer->messages_count }} сообщений</span>
                        </div>
                    </div>

                    @endforeach
                </div>
            @else
                <p class="text-center">{{ __('Нет сообщений') }}</p>
            @endif
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/seller.css') }}">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatItems = document.querySelectorAll('.chat-item');
    
    chatItems.forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            
            const productId = this.closest('.chat-item').getAttribute('data-product-id');
            const storeId = this.closest('.chat-item').getAttribute('data-store-id');
            const userId = this.getAttribute('data-user-id');
            
            window.location.href = `/chat/${productId}/${storeId}/${userId}`;
        });
    });
});

</script>