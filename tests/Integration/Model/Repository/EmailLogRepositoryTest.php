<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Integration\Model\Repository;

use Contenir\Commerce\Model\Entity\EmailLogEntity;
use Contenir\Commerce\Model\Repository\EmailLogRepository;
use Contenir\Db\Model\Repository\RepositoryLookup;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Round-trips one repository against a real (in-memory) database as the
 * reference pattern for the rest; each test builds a fresh database, so no
 * teardown is required.
 */
#[Group('integration')]
#[Group('repository')]
final class EmailLogRepositoryTest extends TestCase
{
    private EmailLogRepository $repository;

    protected function setUp(): void
    {
        $adapter = new Adapter([
            'driver'   => 'Pdo_Sqlite',
            'database' => ':memory:',
        ]);

        $adapter->query(
            'CREATE TABLE email_log ('
                . 'email_log_id INTEGER PRIMARY KEY AUTOINCREMENT, '
                . 'order_id INTEGER NULL, '
                . 'artist_enquiry_id INTEGER NULL, '
                . 'recipient TEXT NOT NULL, '
                . 'subject TEXT NOT NULL, '
                . 'message_class TEXT NULL, '
                . 'status TEXT NOT NULL, '
                . 'error TEXT NULL, '
                . 'created TEXT NULL'
            . ')',
            Adapter::QUERY_MODE_EXECUTE
        );

        $this->repository = new EmailLogRepository(
            $adapter,
            new EmailLogEntity(),
            $this->createStub(RepositoryLookup::class)
        );
    }

    public function testSavesANewLogEntryAndAssignsItsPrimaryKey(): void
    {
        $entry = $this->repository->create([
            'recipient' => 'buyer@example.test',
            'subject'   => 'Your Lon Retreat order',
            'status'    => 'sent',
            'created'   => '2026-08-20 10:00:00',
        ]);

        $this->repository->save($entry);

        $found = $this->repository->findOne(['recipient' => 'buyer@example.test']);

        $this->assertInstanceOf(EmailLogEntity::class, $found);
        $this->assertSame('Your Lon Retreat order', $found->subject);
        $this->assertGreaterThan(0, $found->email_log_id);
    }

    public function testUpdatesAnExistingLogEntryInPlace(): void
    {
        $entry = $this->repository->create([
            'recipient' => 'buyer@example.test',
            'subject'   => 'Your Lon Retreat order',
            'status'    => 'failed',
            'error'     => 'SMTP timeout',
        ]);
        $this->repository->save($entry);

        $saved         = $this->repository->findOne(['recipient' => 'buyer@example.test']);
        $this->assertInstanceOf(EmailLogEntity::class, $saved);
        $saved->status = 'sent';
        $saved->error  = null;
        $this->repository->save($saved);

        $found = $this->repository->findOne(['email_log_id' => $saved->email_log_id]);

        $this->assertInstanceOf(EmailLogEntity::class, $found);
        $this->assertSame('sent', $found->status);
        $this->assertNull($found->error);
        $this->assertCount(1, $this->repository->find());
    }
}
