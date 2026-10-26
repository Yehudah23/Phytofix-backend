<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ContactMessageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'subject' => ['required', 'in:routine,order,product,account,other'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $contactMessage = ContactMessage::create($data);
        $recipient = config('contact.inbox');

        if (! $recipient || config('mail.default') === 'log') {
            report(new \RuntimeException('Contact inbox email delivery is not configured.'));

            return response()->json([
                'message' => 'Message saved to the inbox, but email notification is not configured.',
                'emailSent' => false,
            ], 201);
        }

        try {
            Mail::to($recipient)->send(new ContactMessageReceived($contactMessage));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return response()->json([
                'message' => 'Message saved to the inbox, but email notification could not be delivered.',
                'emailSent' => false,
            ], 201);
        }

        return response()->json(['message' => 'Your message has been sent.', 'emailSent' => true], 201);
    }

    public function index()
    {
        return response()->json(
            ContactMessage::latest()->limit(100)->get()->map(fn (ContactMessage $message) => $this->toView($message))
        );
    }

    public function markRead(ContactMessage $contactMessage)
    {
        $contactMessage->update(['is_read' => true]);

        return response()->json(['message' => 'Contact message marked as read.']);
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return response()->json(['message' => 'Contact message deleted.']);
    }

    private function toView(ContactMessage $message): array
    {
        return [
            'id' => $message->id,
            'name' => $message->name,
            'email' => $message->email,
            'subject' => $message->subject,
            'message' => $message->message,
            'isRead' => $message->is_read,
            'createdAt' => $message->created_at?->valueOf(),
        ];
    }
}
