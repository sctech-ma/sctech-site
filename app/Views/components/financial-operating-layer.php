<?php

declare(strict_types=1);
?>
<figure class="logic-system" data-hero-system aria-labelledby="logic-system-caption">
    <figcaption id="logic-system-caption">
        <span>Interface conceptuelle</span>
        <strong>Logique financière opérable</strong>
    </figcaption>
    <div class="logic-system__canvas">
        <div class="logic-system__toolbar" aria-hidden="true">
            <span>Portefeuille A</span>
            <i></i><i></i><i></i>
        </div>
        <div class="logic-system__flow" aria-label="Données vers reporting">
            <span>Données</span><i aria-hidden="true"></i>
            <span>Règles</span><i aria-hidden="true"></i>
            <span>Validation</span><i aria-hidden="true"></i>
            <span>Workflow</span><i aria-hidden="true"></i>
            <span>Reporting</span>
        </div>
        <div class="logic-system__workspace">
            <section class="logic-system__asset" aria-label="Actif analysé">
                <p>Actif 01</p>
                <strong>Qualification en cours</strong>
                <dl>
                    <div><dt>Référentiel</dt><dd>Version active</dd></div>
                    <div><dt>Données</dt><dd>Structurées</dd></div>
                    <div><dt>État</dt><dd>À valider</dd></div>
                </dl>
            </section>
            <section class="logic-system__rule" aria-label="Règle configurable">
                <div><span>Règle 04</span><b>Active</b></div>
                <p>Si les critères configurés sont satisfaits, transmettre au niveau de contrôle suivant.</p>
                <ul aria-label="Étapes de contrôle">
                    <li class="is-complete">Source qualifiée</li>
                    <li class="is-complete">Règle exécutée</li>
                    <li>Validation requise</li>
                </ul>
            </section>
        </div>
        <div class="logic-system__status">
            <span><i aria-hidden="true"></i> Trace enregistrée</span>
            <strong>Validation humaine requise</strong>
        </div>
    </div>
</figure>

