<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Calendar;
use Carbon\Carbon;
use App\Models\Consultation;

class ConsultationController extends Controller
{
        public function index(): View
    {
        $date = now()->toDateString(); // Текущая дата

        // Генерация всех временных слотов с интервалом в 30 минут
        $allSlots = $this->generateTimeSlots('09:00', '20:00', 30);

        // Получение занятых слотов из Google Calendar
        $busySlots = $this->getBusySlotsFromGoogleCalendar($date);

        // Форматирование занятых слотов для удобства сравнения
        $busySlotsFormatted = array_map(function ($slot) {
            return $slot['start'];
        }, $busySlots);

        // Определение свободных слотов
        $availableSlots = array_diff($allSlots, $busySlotsFormatted);

        // Передача данных в шаблон
        return view('home', [
            'availableSlots' => $availableSlots,
        ]);
    }

    // Метод для записи на консультацию
        public function store(Request $request)
    {
        // Валидация данных
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'scheduled_at' => 'required|date',
        ]);

        // Преобразование строки в объект Carbon
        $scheduledAt = Carbon::createFromFormat('Y-m-d\TH:i', $request->input('scheduled_at'));

        // Проверяем, свободен ли слот
        $isSlotBusy = Consultation::where('scheduled_at', $scheduledAt)->exists();
        if ($isSlotBusy) {
            return redirect()->back()->withErrors(['scheduled_at' => 'Этот слот уже занят!']);
        }

        // Создание записи в базе данных
        $consultation = Consultation::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'scheduled_at' => $scheduledAt,
        ]);

        // Интеграция с Google Calendar
        $googleEventId = $this->createGoogleCalendarEvent(
            $consultation->name,
            $consultation->email,
            $consultation->scheduled_at
        );

        // Сохранение ID события в базу данных
        $consultation->update(['google_event_id' => $googleEventId]);

        // Флеш-сообщение об успехе
        return redirect()->back()->with('success', 'Вы успешно записались на консультацию!');
    }

    private function generateTimeSlots($startTime, $endTime, $intervalMinutes = 30)
    {
        $slots = [];
        $start = Carbon::createFromTimeString($startTime);
        $end = Carbon::createFromTimeString($endTime);

        while ($start->lt($end)) {
            $slots[] = $start->format('H:i'); // Формат времени "часы:минуты"
            $start->addMinutes($intervalMinutes); // Добавляем интервал
        }

        return $slots;
    }

        private function getBusySlotsFromGoogleCalendar($date)
    {
        // Инициализация клиента Google API
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google-calendar-credentials.json'));
        $client->addScope(Calendar::CALENDAR_READONLY);
        $client->fetchAccessTokenWithAssertion();

        // Инициализация сервиса Google Calendar
        $service = new Calendar($client);

        // Форматирование даты для запроса
        $startOfDay = Carbon::parse($date)->startOfDay()->toIso8601String();
        $endOfDay = Carbon::parse($date)->endOfDay()->toIso8601String();


        // Получение событий из Google Calendar
        $events = $service->events->listEvents(
            'primary',
            [
                'timeMin' => $startOfDay,
                'timeMax' => $endOfDay,
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ]
        );

        // Форматирование занятых слотов
        $busySlots = [];
        foreach ($events->getItems() as $event) {
            if ($event->getStart() && $event->getStart()->dateTime) {
                $busySlots[] = [
                    'start' => Carbon::parse($event->getStart()->dateTime)->format('H:i'),
                    'end' => Carbon::parse($event->getEnd()->dateTime)->format('H:i'),
                ];
            }
        }

        return $busySlots;
    }

        private function createGoogleCalendarEvent($name, $email, $scheduledAt)
        {
            // Инициализация клиента Google API
            $client = new Client();
            $client->setAuthConfig(storage_path('app/google-calendar-credentials.json'));
            $client->addScope(Calendar::CALENDAR);
            $client->fetchAccessTokenWithAssertion();

            // Инициализация сервиса Google Calendar
            $service = new Calendar($client);

            // Создание события
            $event = new \Google\Service\Calendar\Event([
                'summary' => 'Консультация с ' . $name,
                'description' => 'Email: ' . $email,
                'start' => [
                    'dateTime' => $scheduledAt->format('Y-m-d\TH:i:s'),
                    'timeZone' => 'Europe/Moscow', // Укажите ваш часовой пояс
                ],
                'end' => [
                    'dateTime' => $scheduledAt->addHour()->format('Y-m-d\TH:i:s'), // Консультация длится 1 час
                    'timeZone' => 'Europe/Moscow',
                ],
            ]);

            // Добавление события в календарь
            $calendarId = 'sonya.konovalova.04@gmail.com'; // Используем основной календарь
            $googleEvent = $service->events->insert($calendarId, $event);

            return $googleEvent->id; // Возвращаем ID созданного события
        }
}