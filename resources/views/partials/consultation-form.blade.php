<section id="consultation" class="py-5">
    <div class="container">
        <h2>Записаться на консультацию</h2>
        <form action="{{ route('consultation.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Имя</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-Mail</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="scheduled_at" class="form-label">Дата и время консультации</label>
                <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Записаться</button>
        </form>
    </div>
    <div class="calendar-container">
        <iframe src="https://calendar.google.com/calendar/embed?src=sonya.konovalova.04%40gmail.com&ctz=Asia%2FYekaterinburg" 
            style="border: 0" 
            width="800" height="600" 
            frameborder="0" 
            scrolling="no">
        </iframe>
    </div>
</section>