<?php

arch('forbids dd(), dump(), ray()')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();
