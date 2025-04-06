
<section id="contact" class="contact-section py-5">
    <div class="container">
        <h2>Связаться с нами</h2>
        <form action="{{ route('contact.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Имя</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="telephone" class="form-label">Телефон</label>
                <input type="text" class="form-control" id="telephone" name="telephone" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-Mail</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Сообщение</label>
                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100">Отправить</button>
        </form>
    </div>
</section>

@if(session('success'))
    <div class="alert alert-success" role="alert">
        {{ session('success') }}
    </div>
@endif
