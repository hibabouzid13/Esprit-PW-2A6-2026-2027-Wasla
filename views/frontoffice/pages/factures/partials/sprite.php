<?php
/**
 * Sprite SVG du Module 2 : logos STEG / SONEDE, Wasel (version fixe) et dégradés.
 * À inclure une seule fois par page, juste après <main>. Aucun script, aucun style en ligne.
 * Les logos sont recadrés par viewBox sur leur contenu réel (mesuré dans les PNG) pour être centrés.
 */
?>
    <svg class="bill-sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="bill-g-drop" x1="0" y1="0" x2=".3" y2="1"><stop offset="0" stop-color="#a6f2ff"/><stop offset=".45" stop-color="#22a6ff"/><stop offset="1" stop-color="#0a4fd0"/></linearGradient>
        <radialGradient id="bill-g-shade" cx=".35" cy=".35" r=".85"><stop offset=".55" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#001a55" stop-opacity=".45"/></radialGradient>
        <linearGradient id="bill-g-bolt" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#d2fa98"/><stop offset="1" stop-color="#4fb81f"/></linearGradient>
        <linearGradient id="bill-g-line" x1="0" x2="1"><stop offset="0" stop-color="#38e1ff" stop-opacity=".1"/><stop offset=".5" stop-color="#38e1ff" stop-opacity=".8"/><stop offset="1" stop-color="#38e1ff" stop-opacity=".1"/></linearGradient>
        <linearGradient id="bill-g-steg" x1="0" x2="1"><stop offset="0" stop-color="#b6f36b"/><stop offset="1" stop-color="#73d13d"/></linearGradient>
        <linearGradient id="bill-g-sonede" x1="0" x2="1"><stop offset="0" stop-color="#38e1ff"/><stop offset="1" stop-color="#00a3d7"/></linearGradient>
      </defs>
      <symbol id="bill-logo-steg" viewBox="25.5 13.5 186 355"><image href="<?= e(asset('img/factures/steg.png')) ?>" width="267" height="378"/></symbol>
      <symbol id="bill-logo-sonede" viewBox="49.5 17.5 329 329"><image href="<?= e(asset('img/factures/sonede.png')) ?>" width="387" height="430"/></symbol>
      <symbol id="bill-wasel" viewBox="0 0 200 240">
        <path d="M100 14 C 92 40, 34 96, 34 156 a66 66 0 0 0 132 0 C 166 96, 108 40, 100 14 Z" fill="url(#bill-g-drop)"/>
        <path d="M100 14 C 92 40, 34 96, 34 156 a66 66 0 0 0 132 0 C 166 96, 108 40, 100 14 Z" fill="url(#bill-g-shade)"/>
        <ellipse cx="70" cy="112" rx="11" ry="24" fill="#fff" opacity=".55" transform="rotate(-25 70 112)"/>
        <ellipse cx="78" cy="150" rx="14" ry="17" fill="#fff"/><ellipse cx="122" cy="150" rx="14" ry="17" fill="#fff"/>
        <circle cx="81" cy="154" r="8" fill="#04203a"/><circle cx="125" cy="154" r="8" fill="#04203a"/>
        <circle cx="84" cy="150" r="3" fill="#fff"/><circle cx="128" cy="150" r="3" fill="#fff"/>
        <ellipse cx="60" cy="178" rx="10" ry="6" fill="#ff8fb1" opacity=".6"/><ellipse cx="140" cy="178" rx="10" ry="6" fill="#ff8fb1" opacity=".6"/>
        <path d="M86 180 q 14 14 28 0" stroke="#04203a" stroke-width="5" stroke-linecap="round" fill="none"/>
        <circle cx="148" cy="204" r="20" fill="url(#bill-g-bolt)" stroke="#fff" stroke-opacity=".8" stroke-width="3"/>
        <path d="M151 190 l-11 16 h9 l-3 13 l12-17 h-9 z" fill="#fff"/>
      </symbol>
    </svg>
