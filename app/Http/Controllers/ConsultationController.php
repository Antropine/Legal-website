<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Calendar;
use Carbon\Carbon;
use App\Models\Consultation;

class ConsultationController extends Controller
{
    // Метод для отображения календаря
    public function showCalendar(Request $request)
    {
        $date = $request->input('date') ?? now()->toDateString(); // По умолчанию текущая дата

        // Генерация всех временных слотов с интервалом в 30 минут
        $allSlots = $this->generateTimeSlots('09:00', '18:00', 30);

        // Получение занятых слотов из Google Calendar
        $busySlots = $this->getBusySlotsFromGoogleCalendar($date);

        // Форматирование занятых слотов для удобства сравнения
        $busySlotsFormatted = array_map(function ($slot) {
            return $slot['start'];
        }, $busySlots);

        // Передача данных в шаблон
        return view('calendar', [
            'date' => $date,
            'timeSlots' => $allSlots,
            'busySlots' => $busySlotsFormatted,
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

        // Преобразование строки в Carbon
        $validatedData['scheduled_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $request->input('scheduled_at'));

        // Создание записи в базе данных
        $consultation = Consultation::create($validatedData);

        // Интеграция с Google Calendar
        $this->createGoogleCalendarEvent($consultation);

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
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google-calendar-credentials.json'));
        $client->addScope(Calendar::CALENDAR_READONLY);
        $client->fetchAccessTokenWithAssertion();

        $service = new Calendar($client);

        $startOfDay = Carbon::parse($date)->startOfDay()->format('Y-m-d\T00:00:00Z');
        $endOfDay = Carbon::parse($date)->endOfDay()->format('Y-m-d\T23:59:59Z');

        $events = $service->events->listEvents(
            'primary',
            [
                'timeMin' => $startOfDay,
                'timeMax' => $endOfDay,
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ]
        );

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

    private function createGoogleCalendarEvent(Consultation $consultation)
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google-calendar-credentials.json'));
        $client->addScope(Calendar::CALENDAR);
        $client->fetchAccessTokenWithAssertion();

        $service = new Calendar($client);

        $event = new \Google\Service\Calendar\Event([
            'summary' => 'Консультация с ' . $consultation->name,
            'description' => 'Email: ' . $consultation->email,
            'start' => [
                'dateTime' => $consultation->scheduled_at->format('Y-m-d\TH:i:s'),
                'timeZone' => 'UTC',
            ],
            'end' => [
                'dateTime' => $consultation->scheduled_at->addHour()->format('Y-m-d\TH:i:s'),
                'timeZone' => 'UTC',
            ],
        ]);

        $calendarId = 'primary';
        $googleEvent = $service->events->insert($calendarId, $event);

        $consultation->update(['google_event_id' => $googleEvent->id]);
    }
}