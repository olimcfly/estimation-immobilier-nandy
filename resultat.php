<?php

declare(strict_types=1);

$typeBien = trim((string) ($_GET['type_bien'] ?? ''));
$ville = trim((string) ($_GET['ville'] ?? ''));
$surfaceM2 = (int) ($_GET['surface_m2'] ?? 0);
$pieces = (int) ($_GET['pieces'] ?? 0);
$etat = trim((string) ($_GET['etat'] ?? ''));

if ($typeBien === '' || $ville === '' || $surfaceM2 <= 0 || $pieces <= 0 || $etat === '') {
    header('Location: /index.php');
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre estimation immobilière</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
<main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="rounded-full border border-blue-200 bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">Page 2 · Résultat d'estimation</p>
            <a href="/index.php" class="text-sm font-medium text-slate-600 underline decoration-slate-300 hover:text-slate-900">← Modifier les informations</a>
        </div>

        <h1 class="mt-4 text-3xl font-bold sm:text-4xl">Votre fourchette de prix estimée</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
            <?= htmlspecialchars($typeBien, ENT_QUOTES); ?> · <?= htmlspecialchars($ville, ENT_QUOTES); ?> · <?= $surfaceM2; ?> m² · <?= $pieces; ?> pièce(s) · <?= htmlspecialchars($etat, ENT_QUOTES); ?>
        </p>

        <div id="loading" class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-slate-600">Calcul de votre estimation par l'API en cours...</div>

        <div id="result" class="mt-8 hidden space-y-6">
            <article class="rounded-2xl bg-slate-900 p-6 text-white sm:p-8">
                <p class="text-xs uppercase tracking-wider text-blue-200">Fourchette recommandée</p>
                <p class="mt-2 text-3xl font-bold sm:text-5xl"><span id="estimation-low">0</span> € — <span id="estimation-high">0</span> €</p>
                <p id="result-explanation" class="mt-4 max-w-2xl text-sm text-slate-200"></p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold">Contexte marché local</h2>
                <p id="result-context" class="mt-2 text-sm leading-6 text-slate-600"></p>
                <p class="mt-3 text-xs text-slate-400">Source estimation: <span id="result-provider">-</span></p>
            </article>

            <article class="rounded-2xl border border-blue-200 bg-blue-50 p-6">
                <h2 class="text-lg font-semibold text-blue-900">Affiner votre estimation avec un conseiller</h2>
                <p class="mt-2 text-sm text-blue-900">Laissez vos coordonnées pour être recontacté rapidement et valider le bon prix de mise en vente.</p>

                <form id="cta-form" class="mt-4 grid gap-3 sm:grid-cols-2">
                    <input type="text" name="prenom" required placeholder="Prénom" class="rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                    <input type="tel" name="telephone" required placeholder="Téléphone" class="rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                    <input type="email" name="email" required placeholder="Email" class="rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none sm:col-span-2">
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 sm:col-span-2">Être recontacté pour affiner mon estimation</button>
                </form>
                <p id="cta-feedback" class="mt-3 hidden rounded-xl border px-4 py-3 text-sm"></p>
            </article>
        </div>

        <div id="error" class="mt-8 hidden rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700"></div>
    </section>
</main>

<script>
const payload = {
    type_bien: <?= json_encode($typeBien, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    ville: <?= json_encode($ville, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    surface_m2: <?= json_encode($surfaceM2); ?>,
    pieces: <?= json_encode($pieces); ?>,
    etat: <?= json_encode($etat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
};

const loadingEl = document.getElementById('loading');
const resultEl = document.getElementById('result');
const errorEl = document.getElementById('error');
const ctaForm = document.getElementById('cta-form');
const ctaFeedback = document.getElementById('cta-feedback');
let estimationData = null;

function formatCurrency(value) {
    return Number(value || 0).toLocaleString('fr-FR');
}

async function loadEstimation() {
    try {
        const response = await fetch('/api/estimation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Erreur lors de la génération de l\'estimation.');
        }

        estimationData = data;
        document.getElementById('estimation-low').textContent = formatCurrency(data.estimation_basse);
        document.getElementById('estimation-high').textContent = formatCurrency(data.estimation_haute);
        document.getElementById('result-context').textContent = data.contexte_marche;
        document.getElementById('result-explanation').textContent = data.explication;
        document.getElementById('result-provider').textContent = data.provider;

        loadingEl.classList.add('hidden');
        resultEl.classList.remove('hidden');
    } catch (error) {
        loadingEl.classList.add('hidden');
        errorEl.textContent = error.message || 'Service indisponible';
        errorEl.classList.remove('hidden');
    }
}

ctaForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    ctaFeedback.classList.add('hidden');

    const formData = new FormData(ctaForm);
    formData.append('type_bien', payload.type_bien);
    formData.append('ville', payload.ville);
    formData.append('surface_tranche', String(payload.surface_m2));
    formData.append('budget_estime', 'Affinage estimation');
    formData.append('estimation_basse', String(estimationData?.estimation_basse || ''));
    formData.append('estimation_haute', String(estimationData?.estimation_haute || ''));
    // Valeurs par défaut pour satisfaire la qualification BANT côté API.
    formData.append('projet', 'Vendre mon bien');
    formData.append('methode_vente', 'Agence');
    formData.append('source_site', 'Landing page estimation');
    formData.append('decisionnaire', 'Oui, seul(e)');
    formData.append('budget_bant', '300 000 € - 500 000 €');
    formData.append('delai', 'Dans les 6 mois');
    formData.append('raison', 'Affiner estimation');

    try {
        const response = await fetch('/api/contact.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Impossible d\'envoyer votre demande.');
        }

        ctaFeedback.className = 'mt-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700';
        ctaFeedback.textContent = 'Merci ! Un conseiller vous recontacte rapidement pour affiner votre estimation.';
        ctaFeedback.classList.remove('hidden');
        ctaForm.reset();
    } catch (error) {
        ctaFeedback.className = 'mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';
        ctaFeedback.textContent = error.message || 'Service temporairement indisponible.';
        ctaFeedback.classList.remove('hidden');
    }
});

loadEstimation();
</script>
</body>
</html>
