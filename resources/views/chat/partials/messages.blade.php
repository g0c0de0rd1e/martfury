@foreach ($chats as $chat)
    <div class="chat-message {{ $chat->sender_id == $user->id ? 'chat-message-right' : 'chat-message-left' }}">
        <div class="message-content">
            <p>{{ $chat->message }}</p>
            @if ($chat->file_path)
                <div class="image-container">
                    <img src="{{ asset('storage/' . $chat->file_path) }}" alt="Изображение из сообщения" class="chat-image">
                    <div class="zoom-icon"></div>
                </div>
            @endif
            <span class="message-time">{{ $chat->created_at->format('d M, Y H:i') }}</span>
        </div>
    </div>
@endforeach
