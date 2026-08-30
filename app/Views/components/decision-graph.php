<?php

declare(strict_types=1);
?>
<div class="decision-graph" data-decision-graph aria-hidden="true">
    <div class="decision-graph__meta">
        <span>SIGNAL / 01</span>
        <span>INTÉGRITÉ DE DÉCISION</span>
    </div>
    <svg viewBox="0 0 720 520" role="presentation" focusable="false">
        <defs>
            <linearGradient id="signal-line" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#62D7CC"/>
                <stop offset="1" stop-color="#2F62FF"/>
            </linearGradient>
            <filter id="signal-soft" x="-20%" y="-20%" width="140%" height="140%">
                <feGaussianBlur stdDeviation="4"/>
            </filter>
        </defs>
        <g class="decision-graph__grid" opacity=".24">
            <path d="M72 40V480M216 40V480M360 40V480M504 40V480M648 40V480"/>
            <path d="M40 104H680M40 208H680M40 312H680M40 416H680"/>
        </g>
        <path class="decision-graph__ghost" d="M74 410C152 410 154 134 254 134S345 362 438 362 524 102 648 102"/>
        <path class="decision-graph__path" pathLength="1" d="M74 410C152 410 154 134 254 134S345 362 438 362 524 102 648 102"/>
        <path class="decision-graph__pulse" pathLength="1" d="M74 410C152 410 154 134 254 134S345 362 438 362 524 102 648 102"/>
        <g class="decision-graph__node decision-graph__node--1" transform="translate(74 410)">
            <circle r="25"/><circle r="5" class="decision-graph__dot"/>
        </g>
        <g class="decision-graph__node decision-graph__node--2" transform="translate(254 134)">
            <circle r="25"/><circle r="5" class="decision-graph__dot"/>
        </g>
        <g class="decision-graph__node decision-graph__node--3" transform="translate(438 362)">
            <circle r="25"/><circle r="5" class="decision-graph__dot"/>
        </g>
        <g class="decision-graph__node decision-graph__node--4" transform="translate(648 102)">
            <circle r="31"/><circle r="7" class="decision-graph__dot"/>
        </g>
    </svg>
    <ol class="decision-graph__labels">
        <li><b>01</b><span>Capter</span></li>
        <li><b>02</b><span>Qualifier</span></li>
        <li><b>03</b><span>Sécuriser</span></li>
        <li><b>04</b><span>Décider</span></li>
    </ol>
    <div class="decision-graph__status"><i></i> Chaîne observable</div>
</div>
