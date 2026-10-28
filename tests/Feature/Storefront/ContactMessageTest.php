<?php

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contactPayload(array $overrides = []): array
{
    return [
        'name' => 'Nusrat Jahan',
        'phone' => '+880 1712-345678',
        'email' => 'nusrat@example.com',
        'message' => 'Do you have this dress in size M?',
        'website' => '',
        ...$overrides,
    ];
}

describe('sending', function () {
    it('saves the message with a normalized mobile number', function () {
        $this->post(route('contact.store'), contactPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect(ContactMessage::sole())
            ->name->toBe('Nusrat Jahan')
            ->mobile->toBe('01712345678')
            ->email->toBe('nusrat@example.com')
            ->is_read->toBeFalse();
    });

    it('works without an email', function () {
        $this->post(route('contact.store'), contactPayload(['email' => null]))->assertSessionHasNoErrors();

        expect(ContactMessage::sole()->email)->toBeNull();
    });

    it('notifies every active admin with a short excerpt and no phone number', function () {
        $admin = User::factory()->create();
        $inactive = User::factory()->inactive()->create();

        $this->post(route('contact.store'), contactPayload(['message' => str_repeat('a', 200)]));

        $message = ContactMessage::sole();
        $data = $admin->notifications()->sole()->data;

        expect(array_keys($data))->toEqualCanonicalizing(['type', 'title', 'body', 'url', 'reference_id'])
            ->and($data['type'])->toBe('message')
            ->and($data['title'])->toBe('New message from Nusrat Jahan')
            ->and(mb_strlen($data['body']))->toBeLessThanOrEqual(83)
            ->and($data['url'])->toBe("/admin/messages/{$message->id}")
            ->and($data['reference_id'])->toBe($message->id)
            ->and(json_encode($data))->not->toContain('01712345678')
            ->and($inactive->notifications()->count())->toBe(0);
    });
});

describe('validation', function () {
    it('rejects invalid input and saves nothing', function (array $overrides, string $field) {
        $this->post(route('contact.store'), contactPayload($overrides))->assertSessionHasErrors($field);

        expect(ContactMessage::count())->toBe(0);
    })->with([
        'missing name' => [['name' => ''], 'name'],
        'missing phone' => [['phone' => ''], 'phone'],
        'not a Bangladeshi mobile' => [['phone' => '12345'], 'phone'],
        'bad email' => [['email' => 'not-an-email'], 'email'],
        'missing message' => [['message' => ''], 'message'],
        'message over 1000 characters' => [['message' => str_repeat('a', 1001)], 'message'],
    ]);
});

describe('abuse', function () {
    it('drops a submission whose honeypot is filled, without saving or notifying', function () {
        $admin = User::factory()->create();

        $this->post(route('contact.store'), contactPayload(['website' => 'https://spam.example']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect(ContactMessage::count())->toBe(0)
            ->and($admin->notifications()->count())->toBe(0);
    });

    it('throttles the sixth message in a minute from one address', function () {
        RateLimiter::clear('contact');

        foreach (range(1, 5) as $attempt) {
            $this->post(route('contact.store'), contactPayload())->assertRedirect();
        }

        $this->post(route('contact.store'), contactPayload())->assertStatus(429);
    });
});
