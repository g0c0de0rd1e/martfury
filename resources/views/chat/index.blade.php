<div class="ps-section--shopping">
    <div class="container">
        <div class="ps-section__header">
            <h1>{{ __('Связаться с продавцом') }}</h1>
        </div>
        <div class="ps-section__content">
            @if ($chats->count())
                <div class="table-responsive chat-window">
                    @foreach ($chats as $chat)
                    <div class="chat-message {{ $chat->user_id == $user->id ? 'chat-message-right' : 'chat-message-left' }}">
                        <div class="message-content">
                            <a href="#">{{ $chat->user->name }}</a>
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
                    <div id="zoomedImageContainer" style="display: none;"></div>
                    @endforeach
                </div>
                <div class="ps-pagination">
                    {!! $chats->links() !!}
                </div>
            @else
                <p class="text-center">{{ __('Нет сообщений') }}</p>
            @endif
            <div class="message-input">
                <form action="{{ route('chat.send', ['productId' => $product->id, 'storeId' => $store->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
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
});

document.addEventListener('DOMContentLoaded', function() {
    const chatImages = document.querySelectorAll('.chat-image');

    chatImages.forEach(image => {
        image.addEventListener('click', function(e) {
            e.preventDefault();
            showZoomedImage(this.src);
        });
    });

    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('close-zoom-button')) {
            closeZoomedImage();
        }
    });

    function showZoomedImage(src) {
        const zoomedContainer = document.getElementById('zoomedImageContainer');
        zoomedContainer.innerHTML = `
            <img src="${src}" alt="Увеличенное изображение">
            <div class="close-zoom-button">&times;</div>
        `;
        zoomedContainer.style.display = 'flex';
    }

    function closeZoomedImage() {
        const zoomedContainer = document.getElementById('zoomedImageContainer');
        zoomedContainer.style.display = 'none';
    }
});

</script>
