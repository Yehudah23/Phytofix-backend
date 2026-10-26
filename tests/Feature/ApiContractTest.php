<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\PrescribedRoutine;
use App\Models\User;
use App\Mail\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_api_returns_frontend_fields_with_a_numeric_price(): void
    {
        $product = Product::create([
            'name' => 'Tea',
            'type' => 'Botanical infusion',
            'price' => 10.50,
            'image' => '/tea.png',
            'alt' => null,
            'description' => null,
            'featured' => true,
        ]);

        $this->getJson('/api/products')->assertOk()
            ->assertJsonPath('0.id', (string) $product->id)
            ->assertJsonPath('0.price', 10.5)
            ->assertJsonPath('0.alt', null)
            ->assertJsonPath('0.featured', true);
    }

    public function test_admin_can_add_a_product(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $adminToken = $admin->createToken('admin-session');

        $this->withToken($adminToken->plainTextToken)
            ->postJson('/api/products', [
                'name' => 'New Herbal Tea',
                'type' => 'Botanical infusion',
                'price' => 12500,
                'image' => '/new-herbal-tea.png',
                'alt' => 'New herbal tea package',
                'description' => 'A new botanical infusion.',
                'featured' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'New Herbal Tea')
            ->assertJsonPath('type', 'Botanical infusion')
            ->assertJsonPath('price', 12500)
            ->assertJsonPath('image', '/new-herbal-tea.png')
            ->assertJsonPath('featured', true);
    }

    public function test_customer_cannot_add_a_product(): void
    {
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $customerToken = $customer->createToken('customer-session');

        $this->withToken($customerToken->plainTextToken)
            ->postJson('/api/products', [
                'name' => 'Unauthorized Product',
                'type' => 'Test',
                'price' => 1,
                'image' => '/unauthorized.png',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_upload_replace_and_delete_a_product_image(): void
    {
        Storage::fake('public');
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $token = $admin->createToken('admin-session');

        $created = $this->withToken($token->plainTextToken)
            ->post('/api/products', [
                'name' => 'Uploaded Product',
                'type' => 'Botanical',
                'price' => 100,
                'image_file' => UploadedFile::fake()->create('first.png', 10, 'image/png'),
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Uploaded Product');
        $firstImagePath = $created->json('image');
        $this->assertStringContainsString('/storage/products/', $firstImagePath);
        $firstStoragePath = substr($firstImagePath, strpos($firstImagePath, '/storage/') + strlen('/storage/'));
        Storage::disk('public')->assertExists($firstStoragePath);
        $productId = $created->json('id');

        $this->withToken($token->plainTextToken)
            ->putJson("/api/products/{$productId}", ['name' => 'Metadata Updated Product'])
            ->assertOk()
            ->assertJsonPath('name', 'Metadata Updated Product')
            ->assertJsonPath('image', $firstImagePath);
        Storage::disk('public')->assertExists($firstStoragePath);

        $updated = $this->post("/api/products/{$productId}", [
            '_method' => 'PUT',
            'name' => 'Updated Uploaded Product',
            'image_file' => UploadedFile::fake()->create('second.png', 10, 'image/png'),
        ], ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$token->plainTextToken])
            ->assertOk()
            ->assertJsonPath('name', 'Updated Uploaded Product');
        $secondImagePath = $updated->json('image');
        $secondStoragePath = substr($secondImagePath, strpos($secondImagePath, '/storage/') + strlen('/storage/'));
        Storage::disk('public')->assertMissing($firstStoragePath);
        Storage::disk('public')->assertExists($secondStoragePath);

        $this->withToken($token->plainTextToken)
            ->deleteJson("/api/products/{$productId}")
            ->assertOk();
        Storage::disk('public')->assertMissing($secondStoragePath);
    }

    public function test_cart_api_returns_frontend_field_names_and_enforces_ownership(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $product = Product::create([
            'name' => 'Tea',
            'type' => 'Botanical infusion',
            'price' => 10,
            'image' => '/tea.png',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/cart', [
            'product_id' => (string) $product->id,
            'name' => 'Forged name',
            'category' => 'Forged category',
            'price' => 0.01,
            'image' => '/forged.png',
            'quantity' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('productId', (string) $product->id)
            ->assertJsonPath('quantity', 2)
            ->assertJsonPath('name', 'Tea')
            ->assertJsonPath('price', 10)
            ->assertJsonMissingPath('product_id');

        $cartId = $response->json('id');
        $anotherUser = User::create([
            'name' => 'Another customer',
            'email' => 'another@example.com',
            'password' => 'password',
        ]);
        Sanctum::actingAs($anotherUser);

        $this->deleteJson("/api/cart/{$cartId}")->assertNotFound();
    }

    public function test_tracked_product_api_returns_camel_case_fields_and_epoch_time(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $product = Product::create([
            'name' => 'Tea',
            'type' => 'Botanical infusion',
            'price' => 10,
            'image' => '/tea.png',
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/user-products', [
            'product_id' => (string) $product->id,
            'product_name' => 'Forged name',
            'product_type' => 'Forged type',
            'product_image' => '/forged.png',
            'total_days' => 30,
        ])->assertCreated()
            ->assertJsonPath('productId', (string) $product->id)
            ->assertJsonPath('productName', 'Tea')
            ->assertJsonPath('productType', 'Botanical infusion')
            ->assertJsonPath('productImage', '/tea.png')
            ->assertJsonPath('currentDay', 1)
            ->assertJsonPath('totalDays', 30)
            ->assertJsonMissingPath('product_id')
            ->assertJsonMissingPath('started_at');
    }

    public function test_admin_content_api_persists_team_and_news_images(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $token = $admin->createToken('admin-session');

        $this->withToken($token->plainTextToken)
            ->postJson('/api/admin/team', [
                'name' => 'Team member',
                'role' => 'Researcher',
                'image' => '/team.png',
            ])->assertCreated()
            ->assertJsonPath('image', '/team.png');

        $this->postJson('/api/admin/news', [
            'tag' => 'Company',
            'title' => 'News headline',
            'image' => '/news.png',
        ])->assertCreated()
            ->assertJsonPath('image', '/news.png');
    }

    public function test_contact_message_is_saved_and_emailed(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp', 'contact.inbox' => 'support@example.com']);

        $this->postJson('/api/contact', [
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'subject' => 'order',
            'message' => 'Can you help with my order?',
        ])->assertCreated();

        $message = ContactMessage::firstOrFail();
        $this->assertSame('Can you help with my order?', $message->message);
        Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail) => $mail->hasTo('support@example.com'));
    }

    public function test_admin_can_manage_contact_inbox_and_customer_cannot_read_it(): void
    {
        $message = ContactMessage::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'subject' => 'order',
            'message' => 'Please help with this order.',
        ]);
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $this->withToken($customer->createToken('customer-session')->plainTextToken)
            ->getJson('/api/admin/contact-messages')
            ->assertForbidden();

        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $token = $admin->createToken('admin-session')->plainTextToken;
        $this->withToken($token)
            ->getJson('/api/admin/contact-messages')
            ->assertOk()
            ->assertJsonPath('0.message', 'Please help with this order.')
            ->assertJsonPath('0.isRead', false);
        $this->withToken($token)
            ->patchJson("/api/admin/contact-messages/{$message->id}/read")
            ->assertOk();
        $this->assertDatabaseHas('contact_messages', ['id' => $message->id, 'is_read' => true]);
        $this->withToken($token)
            ->deleteJson("/api/admin/contact-messages/{$message->id}")
            ->assertOk();
        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }

    public function test_admin_can_prescribe_a_routine_for_an_existing_customer(): void
    {
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $token = $admin->createToken('admin-session')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/prescribed', [
                'user_email' => $customer->email,
                'user_name' => $customer->name,
                'name' => 'Plant care plan',
                'description' => 'A short care plan.',
                'total_days' => 7,
                'steps' => [[
                    'day' => 1,
                    'title' => 'Begin gently',
                    'description' => 'Start with a light application.',
                    'instructions' => 'Use as directed.',
                    'tips' => 'Check the leaves.',
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('userEmail', $customer->email)
            ->assertJsonPath('steps.0.title', 'Begin gently')
            ->assertJsonPath('createdBy', 'Admin');
    }

    public function test_order_uses_catalogue_prices_instead_of_submitted_prices(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $product = Product::create([
            'name' => 'Tea',
            'type' => 'Botanical infusion',
            'price' => 10.50,
            'image' => '/tea.png',
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/orders', [
            'name' => 'Customer',
            'email' => $user->email,
            'phone' => '+2348000000000',
            'address' => 'Test address',
            'city' => 'Akure',
            'items' => [[
                'product_id' => (string) $product->id,
                'name' => 'Forged name',
                'category' => 'Forged category',
                'price' => 0.01,
                'image' => '/forged.png',
                'quantity' => 2,
            ]],
        ])->assertCreated()
            ->assertJsonPath('total', 21)
            ->assertJsonPath('items.0.name', 'Tea')
            ->assertJsonPath('items.0.price', 10.5);
    }

    public function test_customer_cannot_advance_another_customers_prescribed_routine(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);
        $routine = PrescribedRoutine::create([
            'prescribed_id' => 'prescribed-owner-test',
            'user_email' => $owner->email,
            'user_name' => $owner->name,
            'name' => 'Care routine',
            'total_days' => 10,
            'current_day' => 1,
            'created_by' => 'Admin',
        ]);
        $otherCustomer = User::create([
            'name' => 'Other customer',
            'email' => 'other@example.com',
            'password' => 'password',
        ]);
        Sanctum::actingAs($otherCustomer);

        $this->postJson("/api/prescribed/{$routine->prescribed_id}/advance")->assertNotFound();
        $this->assertDatabaseHas('prescribed_routines', [
            'id' => $routine->id,
            'current_day' => 1,
        ]);
    }

    public function test_customer_logout_revokes_only_the_current_access_token(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $currentToken = $user->createToken('current-session');
        $otherToken = $user->createToken('other-session');

        $this->withToken($currentToken->plainTextToken)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }

    public function test_admin_logout_revokes_only_the_current_access_token(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $currentToken = $admin->createToken('current-session');
        $otherToken = $admin->createToken('other-session');

        $this->withToken($currentToken->plainTextToken)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }

    public function test_admin_me_endpoint_returns_admin_role(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $token = $admin->createToken('admin-session');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email', 'admin@phytofix.ng')
            ->assertJsonPath('role', 'admin');
    }

    public function test_customer_using_admin_email_cannot_access_admin_routes(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $token = $user->createToken('customer-session');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/admin/stats')
            ->assertForbidden();
    }

    public function test_admin_tokens_cannot_access_customer_resources(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $token = $admin->createToken('admin-session');

        $this->withToken($token->plainTextToken)->getJson('/api/cart')->assertForbidden();
        $this->withToken($token->plainTextToken)->getJson('/api/orders')->assertForbidden();
        $this->withToken($token->plainTextToken)->getJson('/api/prescribed')->assertForbidden();
        $this->withToken($token->plainTextToken)->getJson('/api/user-products')->assertForbidden();
    }

    public function test_customers_cannot_advance_order_progress(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $order = Order::create([
            'order_id' => 'PHYTO-TEST-123',
            'user_id' => $user->id,
            'customer_email' => $user->email,
            'customer_name' => $user->name,
            'phone' => '+2348000000000',
            'address' => 'Test address',
            'city' => 'Akure',
            'status' => 'placed',
            'simulated_hours' => 0,
            'total' => 10,
            'placed_at' => now(),
        ]);
        $customerToken = $user->createToken('customer-session');

        $this->withToken($customerToken->plainTextToken)
            ->postJson("/api/orders/{$order->order_id}/advance")
            ->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'simulated_hours' => 0]);
    }

    public function test_admins_can_advance_order_progress(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $order = Order::create([
            'order_id' => 'PHYTO-TEST-456',
            'user_id' => $user->id,
            'customer_email' => $user->email,
            'customer_name' => $user->name,
            'phone' => '+2348000000000',
            'address' => 'Test address',
            'city' => 'Akure',
            'status' => 'placed',
            'simulated_hours' => 0,
            'total' => 10,
            'placed_at' => now(),
        ]);
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@phytofix.ng',
            'password' => 'password',
        ]);
        $adminToken = $admin->createToken('admin-session');

        $this->withToken($adminToken->plainTextToken)
            ->postJson("/api/orders/{$order->order_id}/advance")
            ->assertOk()
            ->assertJsonPath('simulatedHours', 24);
    }
}
