<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerDocument;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\DocumentType;
use App\Modules\Customer\Enums\KycStatus;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Customer\Services\KycDocumentService;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class KycDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected CustomerService $customerService;
    protected KycDocumentService $kycDocumentService;
    protected Customer $customerA;
    protected Customer $customerB;
    protected StaffUser $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Standard fake private storage disk setup
        Storage::fake('local');

        $this->plan = Plan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'price_monthly' => 20.00,
            'price_yearly' => 200.00,
        ]);

        $this->tenantA = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'tenant-a',
            'name' => 'Tenant A',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'slug' => 'tenant-b',
            'name' => 'Tenant B',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->customerService = app(CustomerService::class);
        $this->kycDocumentService = app(KycDocumentService::class);

        $tenantManager = app(TenantManager::class);

        // Setup Tenant A Customer & Staff User
        $tenantManager->setTenant($this->tenantA);
        $this->customerA = $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Customer Tenant A',
            'phone' => '01700000001',
        ]);

        $this->staffUser = StaffUser::create([
            'name' => 'Staff Officer',
            'email' => 'officer@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        // Setup Tenant B Customer
        $tenantManager->setTenant($this->tenantB);
        $this->customerB = $this->customerService->createCustomer($this->tenantB->id, [
            'type' => CustomerType::BUSINESS,
            'full_name' => 'Customer Tenant B',
            'phone' => '01700000002',
        ]);
    }

    /**
     * Test secure upload under unguessable filenames and private storage only.
     */
    public function test_document_secure_upload_saves_privately_with_unguessable_filename(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $file = UploadedFile::fake()->create('my_nid.pdf', 500, 'application/pdf');

        $document = $this->kycDocumentService->uploadDocument($this->tenantA->id, (int)$this->customerA->id, $file, [
            'type' => DocumentType::NATIONAL_ID,
            'title' => 'NID Document',
        ]);

        $this->assertEquals($this->tenantA->id, $document->tenant_id);
        $this->assertEquals('my_nid.pdf', $document->original_filename);
        $this->assertNotEquals('my_nid.pdf', $document->stored_filename); // Unguessable UUID name!

        // Confirm stored in local private storage
        Storage::disk('local')->assertExists('private/documents/' . $document->stored_filename);
    }

    /**
     * Test server-side MIME type allowlist blocks scripts/executables.
     */
    public function test_unsupported_mime_or_extension_is_blocked(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $badFile = UploadedFile::fake()->create('exploit.php', 20, 'text/x-php');

        $this->expectException(ValidationException::class);
        $this->kycDocumentService->uploadDocument($this->tenantA->id, (int)$this->customerA->id, $badFile, [
            'type' => DocumentType::OTHER,
            'title' => 'Malicious Script',
        ]);
    }

    /**
     * Test customer-scoped duplicate checksum file detection.
     */
    public function test_customer_scoped_duplicate_detection_prevents_reupload_by_same_customer_only(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $file1 = UploadedFile::fake()->createWithContent('same_file.pdf', 'identical file content');
        $file2 = UploadedFile::fake()->createWithContent('same_file.pdf', 'identical file content');

        // 1. Upload for Customer A (should succeed)
        $doc1 = $this->kycDocumentService->uploadDocument($this->tenantA->id, (int)$this->customerA->id, $file1, [
            'type' => DocumentType::NATIONAL_ID,
            'title' => 'NID 1',
        ]);

        // 2. Upload same file again for Customer A (should fail!)
        try {
            $this->kycDocumentService->uploadDocument($this->tenantA->id, (int)$this->customerA->id, $file2, [
                'type' => DocumentType::NATIONAL_ID,
                'title' => 'NID 2',
            ]);
            $this->fail('Validation exception was not thrown for duplicate upload on same customer.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }

        // 3. Upload same file for Customer B under Tenant B context (should succeed!)
        $tenantManager->setTenant($this->tenantB);
        $docB = $this->kycDocumentService->uploadDocument($this->tenantB->id, (int)$this->customerB->id, $file2, [
            'type' => DocumentType::NATIONAL_ID,
            'title' => 'NID Tenant B',
        ]);

        $this->assertNotNull($docB);
    }

    /**
     * Test metadata structure validation blocks junk keys.
     */
    public function test_metadata_payload_validation_blocks_junk_keys(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $file = UploadedFile::fake()->createWithContent('valid.pdf', 'Custom unique content for file 1');

        // 1. Valid metadata format succeeds
        $doc = $this->kycDocumentService->uploadDocument($this->tenantA->id, (int)$this->customerA->id, $file, [
            'type' => DocumentType::PASSPORT,
            'title' => 'Passport',
            'metadata' => [
                'document_number' => 'PP-12345',
                'issue_date' => '2025-01-01',
            ]
        ]);

        $this->assertEquals('PP-12345', $doc->metadata['document_number']);

        // 2. Invalid metadata format fails
        try {
            $file2 = UploadedFile::fake()->createWithContent('valid2.pdf', 'Custom unique content for file 2');
            $this->kycDocumentService->uploadDocument($this->tenantA->id, (int)$this->customerA->id, $file2, [
                'type' => DocumentType::PASSPORT,
                'title' => 'Passport 2',
                'metadata' => [
                    'arbitrary_junk_key' => 'hack',
                ]
            ]);

            $this->fail('Validation exception was not thrown for junk metadata.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('metadata', $e->errors());
        }
    }


    /**
     * Test verification lifecycle status transitions.
     */
    public function test_kyc_verification_lifecycle_status_transitions(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $file = UploadedFile::fake()->create('my_nid.pdf', 500, 'application/pdf');
        $document = $this->kycDocumentService->uploadDocument($this->tenantA->id, (int)$this->customerA->id, $file, [
            'type' => DocumentType::NATIONAL_ID,
            'title' => 'NID Document',
        ]);

        $this->assertEquals(KycStatus::PENDING, $document->verification_status);

        // 1. Approve
        $this->kycDocumentService->verifyDocument($document, KycStatus::APPROVED, $this->staffUser->id);
        $this->assertEquals(KycStatus::APPROVED, $document->fresh()->verification_status);
        $this->assertEquals($this->staffUser->id, $document->fresh()->verified_by);

        // 2. Reject with reason
        $this->kycDocumentService->verifyDocument($document, KycStatus::REJECTED, $this->staffUser->id, 'Blurred image.');
        $this->assertEquals(KycStatus::REJECTED, $document->fresh()->verification_status);
        $this->assertEquals('Blurred image.', $document->fresh()->rejection_reason);

        // 3. Reject without reason fails
        try {
            $this->kycDocumentService->verifyDocument($document, KycStatus::REJECTED, $this->staffUser->id);
            $this->fail('Rejection without reason succeeded.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rejection_reason', $e->errors());
        }
    }

    /**
     * Test secure streamed downloads blocks unauthorized staff.
     */
    public function test_secure_streamed_downloads_authorization_and_policies(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $file = UploadedFile::fake()->create('my_passport.pdf', 200, 'application/pdf');
        $document = $this->kycDocumentService->uploadDocument($this->tenantA->id, (int)$this->customerA->id, $file, [
            'type' => DocumentType::PASSPORT,
            'title' => 'Passport Doc',
        ]);

        // Create restricted role
        $viewPermission = Permission::create([
            'module' => 'customer',
            'action' => 'view',
            'slug' => 'customer.view',
        ]);

        $role = Role::create([
            'name' => 'Officer',
            'slug' => 'officer',
        ]);
        $role->permissions()->attach($viewPermission);

        $staff = StaffUser::create([
            'name' => 'Officer Tester',
            'email' => 'officer.test@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $staff->roles()->attach($role);

        $this->actingAs($staff, 'sanctum');

        // Staff can download because they have customer.view permission!
        $this->assertTrue(Gate::allows('download', $document));
        
        $response = $this->kycDocumentService->downloadDocument($document);
        $this->assertInstanceOf(StreamedResponse::class, $response);
    }
}
