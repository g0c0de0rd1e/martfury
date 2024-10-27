<div class="ps-section--shopping">
    <div class="container">
        <div class="ps-section__content">
            @if ($chats->count())
                <div class="table-responsive chat-window" id="chatWindow">
                    @include('chat.partials.messages', ['chats' => $chats])
                </div>
                <div id="loading-more" style="display: none;">Загрузка более ранних сообщений...</div>
            @else
                <p class="text-center">{{ __('Нет сообщений') }}</p>
            @endif
            <div class="message-input">
                <form id="messageForm" action="{{ route('chat.sendMessageToUser', ['productId' => $product->id, 'storeId' => $store->id, 'userId' => $userId]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="userId" value="{{ $userId }}">
                    <div class="input-group">
                        <textarea name="message" id="message" rows="2" class="form-control" placeholder="Введите сообщение..." required></textarea>
                        <div class="file-upload-container">
                            <button type="button" class="btn btn-file">
                                <i class="fa fa-plus"></i> Добавить файл
                            </button>
                            <input type="file" name="file" id="file" accept="image/*,.pdf,.doc,.docx" style="display: none;">
                        </div>
                        <div class="preview-container">
                            <img id="previewImage" src="" alt="Превью изображения" style="max-width: 100px; max-height: 100px; display: none;">
                        </div>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">{{ __('Отправить') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="{{ asset('css/chat.css') }}">
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('file');
    const fileButton = document.querySelector('.btn-file');
    const previewImage = document.getElementById('previewImage');
    const chatWindow = document.getElementById('chatWindow');
    const loadingMore = document.getElementById('loading-more');
    const messageForm = document.getElementById('messageForm');
    let page = 1;

    fileButton.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.match('image.*')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewImage.src = '';
            previewImage.style.display = 'none';
        }
    });

    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(messageForm);
        fetch(messageForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Вставляем новое сообщение с нужным шаблоном
                const messageHtml = `<div class="message"><div class="message-content">${data.message.message}</div></div>`;
                chatWindow.insertAdjacentHTML('beforeend', messageHtml);
                chatWindow.scrollTop = chatWindow.scrollHeight; // Прокрутка вниз
                messageForm.reset(); // Сброс формы
                previewImage.style.display = 'none';
            } else {
                console.error('Ошибка отправки сообщения:', data.error);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    });

    chatWindow.addEventListener('scroll', function() {
        if (chatWindow.scrollTop + chatWindow.clientHeight >= chatWindow.scrollHeight) {
            loadMoreMessages();
        }
    });

    function loadMoreMessages() {
        if (loadingMore.style.display !== 'none') return;
        loadingMore.style.display = 'block';
        page++;
        fetch(`{{ route('chat.viewMessage', ['productId' => $product->id, 'storeId' => $store->id, 'userId' => $userId]) }}?page=${page}`)
            .then(response => response.text())
            .then(html => {
                chatWindow.insertAdjacentHTML('beforeend', html);
                loadingMore.style.display = 'none';
            })
            .catch(error => {
                console.error('Ошибка загрузки сообщений:', error);
                loadingMore.style.display = 'none';
            });
    }

    // Переход в низ окна при загрузке
    chatWindow.scrollTop = chatWindow.scrollHeight;
    // Переход в низ окна при новом сообщении
    const observer = new MutationObserver(() => {
        chatWindow.scrollTop = chatWindow.scrollHeight;
    });
    observer.observe(chatWindow, { childList: true });
});
</script>
