<?php
/**
 * Wasel, l'assistant du Module 2 : goutte WASLA (eau) + badge éclair (électricité).
 * Version animée (clignement des yeux, main qui salue) ; les animations sont coupées
 * par prefers-reduced-motion (factures.css). Nécessite partials/sprite.php (dégradés).
 */
?>
          <div class="bill-wasel" role="img" aria-label="Wasel, l'assistant WASLA, vous fait signe">
            <svg viewBox="0 0 200 240" focusable="false" aria-hidden="true">
              <path d="M38 172 q -18 6 -20 26" stroke="url(#bill-g-drop)" stroke-width="12" stroke-linecap="round" fill="none"/>
              <g class="bill-wasel__wave"><path d="M160 158 q 24 -4 28 -34" stroke="url(#bill-g-drop)" stroke-width="12" stroke-linecap="round" fill="none"/><circle cx="188" cy="122" r="9" fill="#8de6ff"/></g>
              <path d="M100 14 C 92 40, 34 96, 34 156 a66 66 0 0 0 132 0 C 166 96, 108 40, 100 14 Z" fill="url(#bill-g-drop)"/>
              <path d="M100 14 C 92 40, 34 96, 34 156 a66 66 0 0 0 132 0 C 166 96, 108 40, 100 14 Z" fill="url(#bill-g-shade)"/>
              <ellipse cx="70" cy="112" rx="11" ry="24" fill="#fff" opacity=".55" transform="rotate(-25 70 112)"/>
              <circle cx="82" cy="84" r="5" fill="#fff" opacity=".6"/>
              <g class="bill-wasel__eyes">
                <ellipse cx="78" cy="150" rx="14" ry="17" fill="#fff"/><ellipse cx="122" cy="150" rx="14" ry="17" fill="#fff"/>
                <circle cx="81" cy="154" r="8" fill="#04203a"/><circle cx="125" cy="154" r="8" fill="#04203a"/>
                <circle cx="84" cy="150" r="3" fill="#fff"/><circle cx="128" cy="150" r="3" fill="#fff"/>
              </g>
              <ellipse cx="60" cy="178" rx="10" ry="6" fill="#ff8fb1" opacity=".6"/><ellipse cx="140" cy="178" rx="10" ry="6" fill="#ff8fb1" opacity=".6"/>
              <path d="M86 180 q 14 14 28 0" stroke="#04203a" stroke-width="5" stroke-linecap="round" fill="none"/>
              <circle cx="148" cy="204" r="20" fill="url(#bill-g-bolt)" stroke="#fff" stroke-opacity=".8" stroke-width="3"/>
              <path d="M151 190 l-11 16 h9 l-3 13 l12-17 h-9 z" fill="#fff"/>
            </svg>
          </div>
