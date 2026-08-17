<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum BookingType: string
{
    case PRIVATE_TRANSFER = 'private_transfer';
    case TAXI = 'taxi';
    case RESTAURANT = 'restaurant';
    case TOUR = 'tour';
    case SPA = 'spa';
    case TICKETS = 'tickets';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRIVATE_TRANSFER => 'Private Transfer',
            self::TAXI => 'Taxi',
            self::RESTAURANT => 'Restaurant',
            self::TOUR => 'Tour',
            self::SPA => 'Spa & Wellness',
            self::TICKETS => 'Tickets',
            self::OTHER => 'Other',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PRIVATE_TRANSFER => 'ph ph-car',
            self::TAXI => 'ph ph-taxi',
            self::RESTAURANT => 'ph ph-fork-knife',
            self::TOUR => 'ph ph-map-trifold',
            self::SPA => 'ph ph-flower-lotus',
            self::TICKETS => 'ph ph-ticket',
            self::OTHER => 'ph ph-bookmark-simple',
        };
    }

    public function getVendorLabel(): string
    {
        return match ($this) {
            self::PRIVATE_TRANSFER => 'Driver',
            self::TAXI => 'Taxi Firm',
            self::RESTAURANT => 'Restaurant',
            self::TOUR => 'Tour Operator',
            self::SPA => 'Spa',
            self::TICKETS => 'Ticket Agent',
            self::OTHER => 'Supplier',
        };
    }

    /**
     * @return array<BookingField>
     */
    public function getFields(): array
    {
        return match ($this) {
            self::PRIVATE_TRANSFER => [
                BookingField::PICKUP,
                BookingField::DROPOFF,
                BookingField::FLIGHT,
                BookingField::PASSENGERS,
                BookingField::VEHICLE,
                BookingField::LUGGAGE,
            ],
            self::TAXI => [
                BookingField::PICKUP,
                BookingField::DROPOFF,
                BookingField::PASSENGERS,
                BookingField::VEHICLE,
            ],
            self::RESTAURANT => [
                BookingField::COVERS,
                BookingField::SEATING,
                BookingField::OCCASION,
                BookingField::DIETARY,
            ],
            self::TOUR => [
                BookingField::MEETING_POINT,
                BookingField::DURATION,
                BookingField::PARTICIPANTS,
                BookingField::LANGUAGE,
            ],
            self::SPA => [
                BookingField::TREATMENT,
                BookingField::THERAPIST,
                BookingField::DURATION,
                BookingField::PARTICIPANTS,
            ],
            self::TICKETS => [
                BookingField::EVENT_NAME,
                BookingField::VENUE,
                BookingField::QUANTITY,
                BookingField::SEATS,
                BookingField::COLLECTION,
            ],
            self::OTHER => [
                BookingField::SUMMARY,
            ],
        };
    }

    /**
     * @return array<BookingField>
     */
    public function getSummaryFields(): array
    {
        return match ($this) {
            self::PRIVATE_TRANSFER, self::TAXI => [BookingField::PICKUP, BookingField::DROPOFF],
            self::RESTAURANT => [BookingField::COVERS, BookingField::SEATING],
            self::TOUR => [BookingField::MEETING_POINT, BookingField::PARTICIPANTS],
            self::SPA => [BookingField::TREATMENT, BookingField::PARTICIPANTS],
            self::TICKETS => [BookingField::EVENT_NAME, BookingField::QUANTITY],
            self::OTHER => [BookingField::SUMMARY],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function vendorLabels(): array
    {
        $labels = [];
        foreach (self::cases() as $type) {
            $labels[$type->value] = $type->getVendorLabel();
        }

        return $labels;
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    public static function detailMeta(): array
    {
        $meta = [];
        foreach (self::cases() as $type) {
            $meta[$type->value] = ['label' => $type->getLabel(), 'icon' => $type->getIcon()];
        }

        return $meta;
    }

    /**
     * @return array<string, self>
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $type) {
            $choices[$type->getLabel()] = $type;
        }

        return $choices;
    }
}
