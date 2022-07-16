@extends('blomstra-digest::emails.layouts.basic')

@section('content')
    <div class="container">
        <p>
            {{ $translator->trans('blomstra-digest.email.digest.greeting', [
                '{recipient_display_name}' => $user->display_name,
            ]) }}
        </p>

        <p>
            {{ $translator->trans('blomstra-digest.email.single.summary') }}
        </p>
    </div>

    @include('blomstra-digest::emails.content.discussion', ['discussion' => $discussion])

    <div class="container">
        <p>{{ $translator->trans('blomstra-digest.email.single.footer') }}</p>
    </div>
@endsection
