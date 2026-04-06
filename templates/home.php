<?php
$homeTitle = defined('HOME_H1') && HOME_H1 !== '' ? HOME_H1 : (defined('SITE_NAME') ? SITE_NAME : '');
$homeSubtitle = defined('HOME_SOUS_TITRE') ? HOME_SOUS_TITRE : '';
$logoPath = defined('LOGO_PATH') ? LOGO_PATH : 'assets/images/logo.png';
$cities = [];
if (defined('CITIES_LIST')) {
    $decoded = json_decode((string) CITIES_LIST, true);
    if (is_array($decoded)) {
        $cities = $decoded;
    }
}
?>
<main class="min-h-screen bg-gradient-to-b from-blue-50 to-white">
    <section class="mx-auto max-w-5xl px-6 py-16">
        <div class="mb-10 text-center">
            <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-height:84px;max-width:220px;object-fit:contain; margin: 0 auto 12px auto;">
            <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-blue-900"><?= htmlspecialchars($homeTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mt-3 text-lg text-slate-600"><?= htmlspecialchars($homeSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mx-auto mt-4 max-w-3xl rounded-xl bg-blue-100 px-4 py-3 text-sm font-semibold text-blue-900">
                Votre estimation à Nandy ne se joue pas à l’échelle de la France : elle se joue rue par rue, quartier par quartier.
            </p>
        </div>

        <div class="mx-auto max-w-3xl rounded-2xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
            <form method="post" action="/" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="prenom" class="mb-2 block text-sm font-medium text-slate-700">Prénom</label>
                    <input id="prenom" name="prenom" type="text" required class="w-full rounded-lg border border-slate-300 px-4 py-3">
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" required class="w-full rounded-lg border border-slate-300 px-4 py-3">
                </div>
                <div>
                    <label for="type_bien" class="mb-2 block text-sm font-medium text-slate-700">Type de bien</label>
                    <select id="type_bien" name="type_bien" required class="w-full rounded-lg border border-slate-300 px-4 py-3">
                        <option value="">Sélectionnez</option>
                        <option value="appartement">Appartement</option>
                        <option value="maison">Maison</option>
                        <option value="terrain">Terrain</option>
                        <option value="commerce">Commerce</option>
                        <option value="immeuble">Immeuble</option>
                    </select>
                </div>
                <div>
                    <label for="surface" class="mb-2 block text-sm font-medium text-slate-700">Surface (m²)</label>
                    <input id="surface" name="surface" type="number" min="1" required class="w-full rounded-lg border border-slate-300 px-4 py-3">
                </div>
                <div class="md:col-span-2">
                    <label for="adresse" class="mb-2 block text-sm font-medium text-slate-700">Adresse</label>
                    <input id="adresse" name="adresse" type="text" required class="w-full rounded-lg border border-slate-300 px-4 py-3">
                </div>
                <div class="md:col-span-2">
                    <label for="ville" class="mb-2 block text-sm font-medium text-slate-700">Ville</label>
                    <select id="ville" name="ville" required class="w-full rounded-lg border border-slate-300 px-4 py-3">
                        <option value="">Sélectionnez</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= htmlspecialchars((string) $c, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $c, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-5 py-3 text-base font-semibold text-white">Recevoir mon estimation</button>
                </div>
            </form>
        </div>

        <section class="mx-auto mt-10 grid max-w-5xl grid-cols-1 gap-5 md:grid-cols-3">
            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Différenciation #1</p>
                <h2 class="mt-2 text-lg font-bold text-slate-900">Analyse micro‑quartier</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Nous comparons votre bien avec des ventes réellement proches à Nandy, Savigny-le-Temple, Cesson et Seine-Port, plutôt qu’avec des moyennes départementales trop larges.
                </p>
            </article>

            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Différenciation #2</p>
                <h2 class="mt-2 text-lg font-bold text-slate-900">Typologie périurbaine maîtrisée</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Maison familiale avec jardin, pavillon récent, appartement en résidence : chaque type de bien est évalué avec ses critères locaux (terrain, stationnement, dépendances, état général).
                </p>
            </article>

            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Différenciation #3</p>
                <h2 class="mt-2 text-lg font-bold text-slate-900">Plan d’action après estimation</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Vous ne recevez pas qu’un chiffre : vous obtenez une fourchette argumentée, les points à valoriser avant mise en vente, et une stratégie adaptée au rythme du secteur de Nandy.
                </p>
            </article>
        </section>
    </section>
</main>
