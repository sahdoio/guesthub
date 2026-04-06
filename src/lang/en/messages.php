<?php

declare(strict_types=1);

return [

    // Authentication
    'welcome' => 'Welcome to GuestHub',
    'login_success' => 'Successfully logged in.',
    'logout_success' => 'Successfully logged out.',
    'register_success' => 'Account created successfully.',
    'invalid_credentials' => 'The provided credentials are incorrect.',

    // Guest
    'guest_created' => 'Guest created successfully.',
    'guest_updated' => 'Guest updated successfully.',
    'guest_deleted' => 'Guest deleted successfully.',

    // Reservation
    'reservation_created' => 'Reservation created successfully.',
    'reservation_confirmed' => 'Reservation confirmed successfully.',
    'reservation_cancelled' => 'Reservation cancelled successfully.',
    'checked_in' => 'Guest checked in successfully.',
    'checked_out' => 'Guest checked out successfully.',
    'special_request_added' => 'Special request added successfully.',
    'special_request_fulfilled' => 'Special request fulfilled.',

    // Room
    'room_created' => 'Room created successfully.',
    'room_updated' => 'Room updated successfully.',
    'room_deleted' => 'Room deleted successfully.',
    'room_status_changed' => 'Room status updated successfully.',

    // Errors
    'not_found' => ':resource not found.',
    'unauthorized' => 'You are not authorized to perform this action.',
    'no_rooms_available' => 'No rooms available for the selected type and period.',
    'invalid_state_transition' => 'This action cannot be performed in the current state.',
    'max_special_requests' => 'Maximum of :max special requests per reservation.',

];
