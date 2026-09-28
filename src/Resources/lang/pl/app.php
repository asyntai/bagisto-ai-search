<?php

/**
 * Asyntai AI Search for Bagisto: Polish strings.
 *
 * Generated from translations.json by render_lang.py. Edit the JSON.
 */

return [
    'admin' => [
        'menu' => [
            'title' => 'Wyszukiwarka AI',
        ],

        'acl' => [
            'connection' => 'Łączenie i rozłączanie',
            'settings'   => 'Zmiana ustawień',
        ],

        'title' => 'Wyszukiwarka AI Asyntai',

        'hero' => [
            'title'   => 'Połącz swoje konto Asyntai',
            'text'    => 'Zaloguj się lub załóż bezpłatne konto. Odczytujemy Twój katalog i strony, a pasek włącza się sam, gdy będą gotowe.',
            'button'  => 'Połącz Asyntai',
            'point_1' => 'Produkty, kategorie i strony w jednym polu wyszukiwania.',
            'point_2' => 'Aktualne ceny i stany magazynowe prosto z Twojego sklepu.',
            'point_3' => 'Gdy limit się wyczerpie, wraca Twoja zwykła wyszukiwarka.',
        ],

        'headings' => [
            'live'       => 'Działa w Twoim sklepie',
            'setting_up' => 'Przygotowujemy Twój sklep',
            'blocked'    => 'Połączono, ale jeszcze nie w Twoim sklepie',
            'unknown'    => 'W tej chwili nie możemy połączyć się z Asyntai',
        ],

        'status' => [
            'not_connected'  => 'Jeszcze nie połączono.',
            'live_pages'     => 'Przeszukujemy Twoje strony.',
            'live_products'  => 'Przeszukujemy :count produktów oraz Twoje strony.',
            'plan'           => 'Twój plan nie obejmuje jeszcze paska wyszukiwania AI. Twoja zwykła wyszukiwarka nadal działa, więc w sklepie nic się nie zmieniło.',
            'limit'          => 'Wykorzystałeś wszystkie odpowiedzi w tym miesiącu. Twoja zwykła wyszukiwarka nadal działa, a pasek wróci po odnowieniu limitu.',
            'reading'        => 'Odczytujemy Twój sklep. Zajmie to kilka minut, potem pasek włączy się sam.',
            'unknown_widget' => 'Ten sklep nie jest już połączony z Asyntai. Połącz go ponownie powyżej.',
            'unreachable'    => 'Twój sklep jest nadal połączony. Sprawdzimy to wkrótce ponownie, a w międzyczasie w sklepie nic się nie zmieniło.',
            'off'            => 'Pasek wyszukiwania nie działa.',
        ],

        'allowance'    => 'Pozostało :left z :limit odpowiedzi w tym miesiącu. Wyszukiwania i odpowiedzi czatu dzielą ten sam limit.',
        'connected_as' => 'Połączono jako :email.',
        'dashboard'    => 'Otwórz panel Asyntai',
        'analytics'    => 'Zobacz, czego szukali klienci',
        'check_now'    => 'Sprawdź ponownie',
        'disconnect'   => 'Odłącz ten sklep',

        'preview' => [
            'title'  => 'Wypróbuj na własnym katalogu',
            'text'   => 'Otwórz swój prawdziwy pasek wyszukiwania i wpisz coś, czego szukałby klient. Wyszukiwania wykonane tam nie wliczają się do miesięcznego limitu.',
            'button' => 'Wypróbuj swój pasek wyszukiwania',
        ],

        'settings' => [
            'title'             => 'Ustawienia',
            'placement'         => 'Gdzie pojawia się pasek',
            'placement_help'    => 'Tryb zastąpienia zajmuje miejsce pola wyszukiwania, które pokazuje Twój motyw. Naciśnięcie Enter bez wybrania wyniku nadal otwiera Twoją zwykłą stronę wyników.',
            'placement_replace' => 'W miejscu mojego pola wyszukiwania',
            'placement_manual'  => 'Tylko tam, gdzie wstawi go mój motyw',
            'selector'          => 'Pole wyszukiwania do zastąpienia',
            'selector_help'     => 'Zostaw puste, a znajdziemy je sami. Podaj selektor CSS tylko wtedy, gdy wybierzemy niewłaściwe pole.',
            'placeholder'       => 'Tekst podpowiedzi',
            'placeholder_help'  => 'Zostaw puste, a każdy klient zobaczy go w swoim języku.',
            'accent'            => 'Kolor akcentu',
            'accent_help'       => 'Zostaw puste, aby użyć koloru ustawionego w panelu Asyntai.',
            'feed'              => 'Udostępnij mój katalog Asyntai',
            'feed_help'         => 'Ceny, stany magazynowe i linki do produktów pochodzą prosto z Twojego sklepu, więc wyniki nigdy nie są nieaktualne. Wyłącz to, a pasek będzie przeszukiwał tylko Twoje strony.',
            'save'              => 'Zapisz',
            'saved'             => 'Ustawienia zapisane.',
        ],

        'theme' => [
            'title' => 'W Twoim motywie',
            'text'  => 'Aby umieścić pasek w wybranym przez siebie miejscu, dodaj to tam, gdzie ma się pojawić. Dopóki pasek jest wyłączony, miejsce pozostaje puste.',
        ],

        'js' => [
            'preparing'   => 'Przygotowywanie...',
            'waiting'     => 'Czekamy, aż zakończysz w oknie Asyntai...',
            'saving'      => 'Połączono. Zapisywanie...',
            'blocked'     => 'Przeglądarka zablokowała wyskakujące okno. Zezwól na wyskakujące okna i spróbuj ponownie lub otwórz stronę logowania linkiem poniżej.',
            'open_link'   => 'Otwórz stronę logowania Asyntai',
            'failed'      => 'Nie udało się połączyć. Spróbuj ponownie.',
            'timeout'     => 'Upłynął czas oczekiwania na okno Asyntai. Spróbuj ponownie.',
            'signed_out'  => 'Bagisto wylogowało Cię. Zaloguj się ponownie i spróbuj jeszcze raz.',
            'confirm'     => 'Odłączyć ten sklep od Asyntai? Pasek wyszukiwania przestanie działać, a wróci Twoja zwykła wyszukiwarka.',
            'unreachable' => 'Nie można połączyć się z Asyntai. Sprawdź, czy ten sklep może wysyłać wychodzące żądania HTTPS.',
            'expired'     => 'Ta próba połączenia wygasła. Naciśnij ponownie Połącz.',
            'forbidden'   => 'Twoja rola na to nie pozwala. Poproś właściciela sklepu o nadanie jej uprawnienia Wyszukiwarka AI.',
        ],

    ],
];
