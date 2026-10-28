<?php

arch()->preset()->php();

arch('native components follow the SuperNative conventions')
    ->expect('App\NativeComponents')
    ->toExtend('Native\Mobile\Edge\NativeComponent')
    ->toHaveMethod('render');

arch('diagnosis services stay provider-agnostic')
    ->expect('App\Services\Diagnosis')
    ->not->toUse('Symfony\AI\Platform\Bridge');

arch('native components do not import provider bridges')
    ->expect('App\NativeComponents')
    ->not->toUse('Symfony\AI\Platform\Bridge');
