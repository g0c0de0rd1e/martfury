<div class="ps-section--shopping pt-40">
    <div class="container">
        <div class="ps-section__header">
            <h1>{{ __('Chat') }}</h1>
        </div>
        <div class="ps-section__content">
            @if ($messages->total())
                <div class="table-responsive">
                    <table class="table ps-table--chat ps-table--responsive">
                        <thead>
                        <tr>
                            <th></th>
                            <th class="text-left">{{ __('Sender') }}</th>
                            <th class="text-left">{{ __('Message') }}</th>
                            <th class="text-left">{{ __('Date') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach ($messages as $message)
                                <tr>
                                    <td data-label="{{ __('Remove') }}">&nbsp;<a class="js-remove-from-chat-button" href="#" data-url="{{ route('messages.destroy', $message->id) }}"><i class="icon-cross"></i></a></td>
                                    <td data-label="{{ __('Sender') }}">
                                        <div class="ps-product--cart">
                                            <div class="ps-product__content">
                                                <a href="#">{{ $message->name }}</a>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Message') }}">
                                        <div class="ps-product--cart">
                                            <div class="ps-product__content">
                                                {{ $message->content }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-left" data-label="{{ __('Date') }}">
                                        {{ $message->created_at->format('d M, Y H:i') }}
                                    </td>
                                    <td data-label="{{ __('Action') }}">
                                        <a class="btn btn-secondary" href="{{ route('messages.show', $message->id) }}">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="ps-pagination">
                        {!! $messages->links() !!}
                    </div>
                </div>
            @else
                <p class="text-center">{{ __('No messages found!') }}</p>
            @endif
            <a href="{{ route('my.page') }}" class="btn btn-primary mt-3">Моя страница</a>
        </div>
    </div>
</div>
