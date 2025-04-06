<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Валидация данных
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        // Сохранение данных в базу
        Contact::create($validatedData);

        // Перенаправление с сообщением об успехе
        return redirect()->back()->with('success', 'Сообщение успешно отправлено!');
    }
}
