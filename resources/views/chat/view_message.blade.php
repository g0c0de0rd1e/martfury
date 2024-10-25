
<div class="ps-section--shopping pt-40">
    <div class="container">
        <div class="ps-section__header">
            <h1>{{ __('Переписка с покупателем') }}</h1>
        </div>
        <div class="ps-section__content">
            @if ($chats->count())
                <div class="table-responsive">
                    <table class="table ps-table--chat ps-table--responsive">
                        <thead>
                            <tr>
                                <th class="text-left">{{ __('Sender') }}</th>
                                <th class="text-left">{{ __('Message') }}</th>
                                <th class="text-left">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chats as $chat)
                                <tr>
                                    <td data-label="{{ __('Sender') }}">
                                        <div class="ps-product--cart">
                                            <div class="ps-product__content">
                                                <a href="#">{{ $chat->user->name }}</a>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Message') }}">
                                        <div class="ps-product--cart">
                                            <div class="ps-product__content">
                                                {{ $chat->message }}
                                                @if ($chat->file_path)
                                                    <p><a href="{{ asset('storage/' . $chat->file_path) }}" target="_blank">{{ __('Скачать файл') }}</a></p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-left" data-label="{{ __('Date') }}">
                                        {{ $chat->created_at->format('d M, Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="ps-pagination">
                        {!! $chats->links() !!}
                    </div>
                </div>
            @else
                <p class="text-center">{{ __('Нет сообщений') }}</p>
            @endif
            <form action="{{ route('chat.send', ['productId' => $product->id, 'storeId' => $store->id, 'userId' => $user->id]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div>
                    <label for="message">{{ __('Сообщение:') }}</label>
                    <textarea name="message" id="message" rows="5" required></textarea>
                </div>
                <div>
                    <label for="file">{{ __('Прикрепить файл:') }}</label>
                    <input type="file" name="file" id="file" accept="image/*,.pdf,.doc,.docx">
                </div>
                <button type="submit">{{ __('Отправить') }}</button>
            </form>
        </div>
    </div>
</div>