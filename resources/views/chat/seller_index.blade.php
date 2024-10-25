<div class="ps-section--shopping pt-40">
    <div class="container">
        <div class="ps-section__header">
            <h1>{{ __('Чаты с покупателями') }}</h1>
        </div>
        <div class="ps-section__content">
            @if ($customers->count())
                <div class="table-responsive">
                    <table class="table ps-table--chat ps-table--responsive">
                        <thead>
                            <tr>
                                <th>{{ __('Покупатель') }}</th>
                                <th class="text-left">{{ __('Сообщений') }}</th>
                                <th class="text-left">{{ __('Последнее сообщение') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                <tr>
                                    <td data-label="{{ __('Покупатель') }}">
                                        <div class="ps-product--cart">
                                            <div class="ps-product__content">
                                                <a href="{{ route('chat.viewMessage', ['productId' => $product->id, 'storeId' => $store->id, 'userId' => $customer->user_id]) }}">
                                                    {{ $customer->user->name }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Сообщений') }}">{{ $customer->messages_count }}</td>
                                    <td data-label="{{ __('Последнее сообщение') }}">
                                        {{ $customer->last_message->created_at->format('d M, Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center">{{ __('Нет сообщений') }}</p>
            @endif
        </div>
    </div>
</div>
