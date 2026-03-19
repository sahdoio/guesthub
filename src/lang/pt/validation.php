<?php

return [

    'required' => 'O campo :attribute e obrigatorio.',
    'email' => 'O campo :attribute deve ser um endereco de email valido.',
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'max' => [
        'string' => 'O campo :attribute nao pode exceder :max caracteres.',
    ],
    'unique' => 'O :attribute informado ja esta em uso.',
    'confirmed' => 'A confirmacao do campo :attribute nao corresponde.',
    'date' => 'O campo :attribute deve ser uma data valida.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal' => 'O campo :attribute deve ser uma data posterior ou igual a :date.',
    'in' => 'O :attribute selecionado e invalido.',

    'attributes' => [
        'name' => 'nome',
        'email' => 'email',
        'password' => 'senha',
        'phone' => 'telefone',
        'document' => 'documento',
        'check_in' => 'check-in',
        'check_out' => 'check-out',
        'room_type' => 'tipo de quarto',
        'room_number' => 'numero do quarto',
        'reason' => 'motivo',
        'description' => 'descricao',
        'floor' => 'andar',
        'capacity' => 'capacidade',
        'price_per_night' => 'preco por noite',
        'loyalty_tier' => 'nivel de fidelidade',
    ],

];
