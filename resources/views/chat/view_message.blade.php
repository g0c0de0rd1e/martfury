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
    let loading = false;
    let initialLoad = true;

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
                const message = data.message;
                const messageHtml = `
                    <div class="chat-message ${message.sender_id == ${Auth::guard('customer')->id()} ? 'chat-message-right' : 'chat-message-left'}">
                        <div class="message-content">
                            <p>${message.message}</p>
                            ${message.file_path ? `<div class="image-container">
                                <img src="/storage/${message.file_path}" alt="Изображение из сообщения" class="chat-image">
                                <div class="zoom-icon"></div>
                            </div>` : ''}
                            <span class="message-time">${new Date(message.created_at).toLocaleString()}</span>
                        </div>
                    </div>`;
                chatWindow.insertAdjacentHTML('beforeend', messageHtml); // Вставляем в конец
                chatWindow.scrollTop = chatWindow.scrollHeight; // Прокрутка вниз
                messageForm.reset(); // Сброс формы
                previewImage.style.display = 'none';
                checkForNewMessages(); // Проверка новых сообщений при отправке нового сообщения
            } else {
                console.error('Ошибка отправки сообщения:', data.error);
            }
        })
        .catch(error => console.error('Ошибка:', error));
    });

    chatWindow.addEventListener('scroll', function() {
        if (chatWindow.scrollTop === 0 && !loading) {
            loadMoreMessages();
        }
    });

    function loadMoreMessages() {
        if (loadingMore.style.display !== 'none' || loading) return;
        loading = true;
        loadingMore.style.display = 'block';
        page++;
        const previousScrollHeight = chatWindow.scrollHeight;
        fetch(`{{ route('chat.index', ['productId' => $product->id, 'storeId' => $store->id]) }}?page=${page}`)
            .then(response => response.text())
            .then(html => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const newMessages = tempDiv.querySelectorAll('.chat-message');
                newMessages.forEach(message => {
                    chatWindow.insertBefore(message, chatWindow.firstChild);
                });
                const newScrollHeight = chatWindow.scrollHeight;
                chatWindow.scrollTop = newScrollHeight - previousScrollHeight; // Возвращаем позицию прокрутки
                loadingMore.style.display = 'none';
                loading = false;
            })
            .catch(error => {
                console.error('Ошибка загрузки сообщений:', error);
                loadingMore.style.display = 'none';
                loading = false;
            });
    }

    // Прокрутка вниз только при первой загрузке
    if (initialLoad) {
        chatWindow.scrollTop = chatWindow.scrollHeight;
        initialLoad = false;
    }

    function checkForNewMessages() {
        fetch(`{{ route('chat.index', ['productId' => $product->id, 'storeId' => $store->id]) }}?page=1`)
            .then(response => response.text())
            .then(html => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const newMessages = tempDiv.querySelectorAll('.chat-message');
                const lastMessageId = chatWindow.lastElementChild ? chatWindow.lastElementChild.dataset.messageId : 0;
                let newContent = '';
                newMessages.forEach(message => {
                    if (message.dataset.messageId > lastMessageId) {
                        newContent += message.outerHTML;
                    }
                });
                if (newContent) {
                    chatWindow.insertAdjacentHTML('beforeend', newContent); // Вставляем новые сообщения в конец
                    chatWindow.scrollTop = chatWindow.scrollHeight; // Прокрутка вниз
                }
            })
            .catch(error => console.error('Ошибка проверки новых сообщений:', error));
    }

    // Проверка новых сообщений при действиях пользователя
    chatWindow.addEventListener('mousemove', function() {
        checkForNewMessages();
    });
});
</script>
