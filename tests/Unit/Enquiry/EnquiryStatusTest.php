<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\Unit\Enquiry;

use Contenir\Commerce\Enquiry\EnquiryStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class EnquiryStatusTest extends TestCase
{
    #[DataProvider('statusProvider')]
    public function testMapsDatabaseValueToLabel(string $value, EnquiryStatus $expected, string $label): void
    {
        $status = EnquiryStatus::from($value);

        $this->assertSame($expected, $status);
        $this->assertSame($label, $status->label());
    }

    /**
     * @return array<string, array{string, EnquiryStatus, string}>
     */
    public static function statusProvider(): array
    {
        return [
            'new'          => ['new', EnquiryStatus::NewEnquiry, 'New'],
            'under review' => ['under_review', EnquiryStatus::UnderReview, 'Under review'],
            'shortlisted'  => ['shortlisted', EnquiryStatus::Shortlisted, 'Shortlisted'],
            'declined'     => ['declined', EnquiryStatus::Declined, 'Declined'],
            'accepted'     => ['accepted', EnquiryStatus::Accepted, 'Accepted'],
        ];
    }
}
