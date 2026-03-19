<?php

return [

    // Authentication
    'welcome' => 'Bem-vindo ao GuestHub',
    'login_success' => 'Login realizado com sucesso.',
    'logout_success' => 'Logout realizado com sucesso.',
    'register_success' => 'Conta criada com sucesso.',
    'invalid_credentials' => 'As credenciais fornecidas estao incorretas.',

    // Guest
    'guest_created' => 'Hospede criado com sucesso.',
    'guest_updated' => 'Hospede atualizado com sucesso.',
    'guest_deleted' => 'Hospede removido com sucesso.',

    // Reservation
    'reservation_created' => 'Reserva criada com sucesso.',
    'reservation_confirmed' => 'Reserva confirmada com sucesso.',
    'reservation_cancelled' => 'Reserva cancelada com sucesso.',
    'checked_in' => 'Check-in realizado com sucesso.',
    'checked_out' => 'Check-out realizado com sucesso.',
    'special_request_added' => 'Pedido especial adicionado com sucesso.',
    'special_request_fulfilled' => 'Pedido especial atendido.',

    // Room
    'room_created' => 'Quarto criado com sucesso.',
    'room_updated' => 'Quarto atualizado com sucesso.',
    'room_deleted' => 'Quarto removido com sucesso.',
    'room_status_changed' => 'Status do quarto atualizado com sucesso.',

    // Errors
    'not_found' => ':resource nao encontrado(a).',
    'unauthorized' => 'Voce nao tem permissao para realizar esta acao.',
    'no_rooms_available' => 'Nenhum quarto disponivel para o tipo e periodo selecionados.',
    'invalid_state_transition' => 'Esta acao nao pode ser realizada no estado atual.',
    'max_special_requests' => 'Maximo de :max pedidos especiais por reserva.',

];
