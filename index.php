<?php

declare(strict_types=1);

$configPath = __DIR__ . '/config/config.php';
$config = [];
$siteInstalled = false;

if (is_file($configPath)) {
    $loaded = require $configPath;

    if (is_array($loaded)) {
        $config = $loaded;
        $siteInstalled = !empty($config['installed']);
    } else {
        $siteInstalled = true;
    }
}

if (!$siteInstalled) {
    header('Location: /install/index.php');
    exit;
}

$agenceNom = (string) ($config['agence_nom'] ?? 'Votre agence');
$villePrincipale = (string) ($config['ville_principale'] ?? 'Nandy');
$villes = $config['villes'] ?? [$villePrincipale, 'Savigny-le-Temple', 'Cesson', 'Vert-Saint-Denis', 'Moissy-Cramayel', 'Lieusaint'];
if (!is_array($villes) || $villes === []) {
    $villes = [$villePrincipale, 'Savigny-le-Temple', 'Cesson', 'Vert-Saint-Denis'];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimation immobilière · <?= htmlspecialchars($agenceNom, ENT_QUOTES); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<main class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-16">
    <section class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
        <div>
            <p class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-4 py-1 text-sm font-semibold text-blue-700">Estimation instantanée · sans engagement</p>
            <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">Connaissez la valeur de votre bien en moins d'une minute.</h1>
            <p class="mt-4 max-w-xl text-base leading-7 text-slate-600 sm:text-lg">Renseignez les informations clés de votre bien à <?= htmlspecialchars($villePrincipale, ENT_QUOTES); ?>. Vous serez redirigé vers une page de résultat claire avec une fourchette de prix et un contexte marché.</p>

            <div class="mt-6 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4">✅ Formulaire rapide, mobile-first</div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">✅ Résultat lisible et actionnable</div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7 lg:p-8">
            <h2 class="text-2xl font-bold">Page 1 · Votre bien</h2>
            <p class="mt-2 text-sm text-slate-500">Tous les champs sont utiles pour affiner votre estimation.</p>

            <form action="/resultat.php" method="get" class="mt-6 space-y-5" novalidate>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="type_bien" class="mb-2 block text-sm font-medium text-slate-700">Type de bien</label>
                        <select id="type_bien" name="type_bien" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-base focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="">Sélectionner</option>
                            <option value="Appartement">Appartement</option>
                            <option value="Maison">Maison</option>
                            <option value="Terrain">Terrain</option>
                            <option value="Local commercial">Local commercial</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="ville" class="mb-2 block text-sm font-medium text-slate-700">Ville</label>
                        <select id="ville" name="ville" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-base focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="">Sélectionner</option>
                            <?php foreach ($villes as $ville): ?>
                                <option value="<?= htmlspecialchars((string) $ville, ENT_QUOTES); ?>"><?= htmlspecialchars((string) $ville, ENT_QUOTES); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="surface_m2" class="mb-2 block text-sm font-medium text-slate-700">Surface (m²)</label>
                        <input id="surface_m2" name="surface_m2" type="number" min="12" max="600" step="1" required placeholder="Ex: 87" class="w-full rounded-xl border border-slate-300 px-4 py-3.5 text-base focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label for="pieces" class="mb-2 block text-sm font-medium text-slate-700">Nombre de pièces</label>
                        <input id="pieces" name="pieces" type="number" min="1" max="15" step="1" required placeholder="Ex: 4" class="w-full rounded-xl border border-slate-300 px-4 py-3.5 text-base focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="etat" class="mb-2 block text-sm font-medium text-slate-700">État du bien</label>
                        <select id="etat" name="etat" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-base focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="">Sélectionner</option>
                            <option value="à rénover">À rénover</option>
                            <option value="bon">Bon état</option>
                            <option value="excellent">Excellent état</option>
                            <option value="neuf">Neuf / récent</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-4 text-base font-semibold text-white transition hover:bg-blue-700">Voir mon estimation →</button>
                <p class="text-center text-xs text-slate-500">En cliquant, vous accédez à la page résultat (étape 2).</p>
            </form>
        </div>
    </section>
</main>
</body>
</html>
