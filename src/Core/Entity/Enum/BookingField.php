<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity\Enum;

enum BookingField: string
{
    case PICKUP = 'pickup';
    case DROPOFF = 'dropoff';
    case FLIGHT = 'flight';
    case PASSENGERS = 'passengers';
    case VEHICLE = 'vehicle';
    case LUGGAGE = 'luggage';
    case COVERS = 'covers';
    case SEATING = 'seating';
    case OCCASION = 'occasion';
    case DIETARY = 'dietary';
    case MEETING_POINT = 'meeting_point';
    case DURATION = 'duration';
    case PARTICIPANTS = 'participants';
    case LANGUAGE = 'language';
    case TREATMENT = 'treatment';
    case THERAPIST = 'therapist';
    case EVENT_NAME = 'event_name';
    case VENUE = 'venue';
    case QUANTITY = 'quantity';
    case SEATS = 'seats';
    case COLLECTION = 'collection';
    case SUMMARY = 'summary';

    public function getLabel(): string
    {
        return match ($this) {
            self::PICKUP => 'Pickup',
            self::DROPOFF => 'Drop Off',
            self::FLIGHT => 'Flight',
            self::PASSENGERS => 'Passengers',
            self::VEHICLE => 'Vehicle',
            self::LUGGAGE => 'Luggage',
            self::COVERS => 'Covers',
            self::SEATING => 'Seating',
            self::OCCASION => 'Occasion',
            self::DIETARY => 'Dietary',
            self::MEETING_POINT => 'Meeting Point',
            self::DURATION => 'Duration',
            self::PARTICIPANTS => 'Participants',
            self::LANGUAGE => 'Language',
            self::TREATMENT => 'Treatment',
            self::THERAPIST => 'Therapist',
            self::EVENT_NAME => 'Event',
            self::VENUE => 'Venue',
            self::QUANTITY => 'Tickets',
            self::SEATS => 'Seats',
            self::COLLECTION => 'Collection',
            self::SUMMARY => 'Summary',
        };
    }

    public function getPlaceholder(): string
    {
        return match ($this) {
            self::PICKUP => 'Pickup Location',
            self::DROPOFF => 'Drop Off Location',
            self::FLIGHT => 'Flight Number',
            self::PASSENGERS => 'No. of Passengers',
            self::VEHICLE => 'Vehicle Type',
            self::LUGGAGE => 'Luggage',
            self::COVERS => 'No. of Covers',
            self::SEATING => 'Seating Preference',
            self::OCCASION => 'Occasion',
            self::DIETARY => 'Dietary Requirements',
            self::MEETING_POINT => 'Meeting Point',
            self::DURATION => 'Duration',
            self::PARTICIPANTS => 'No. of Participants',
            self::LANGUAGE => 'Language',
            self::TREATMENT => 'Treatment',
            self::THERAPIST => 'Therapist',
            self::EVENT_NAME => 'Event Name',
            self::VENUE => 'Venue',
            self::QUANTITY => 'No. of Tickets',
            self::SEATS => 'Seats',
            self::COLLECTION => 'Collection Details',
            self::SUMMARY => 'What is being booked',
        };
    }

    /**
     * @return array<BookingType>
     */
    public function getTypes(): array
    {
        return array_values(array_filter(
            BookingType::cases(),
            fn (BookingType $type) => in_array($this, $type->getFields(), true),
        ));
    }
}
