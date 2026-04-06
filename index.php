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
        // Compatibilité legacy: config en constantes define(...)
        $siteInstalled = true;
    }
}

if (!$siteInstalled) {
    header('Location: /install/index.php');
    exit;
}

$agenceNom = (string) ($config['agence_nom'] ?? 'Votre agence');
$villePrincipale = (string) ($config['ville_principale'] ?? 'Nandy');
$logo = (string) ($config['logo'] ?? '');
$couleur = (string) ($config['couleur'] ?? '#1e3a5f');
$h1 = (string) ($config['h1_titre'] ?? ('Combien vaut votre bien à ' . $villePrincipale . ' ?'));
$sousTitre = (string) ($config['sous_titre'] ?? 'À Nandy, vendre au bon prix dès le départ est la clé : obtenez une estimation stratégique pour sécuriser votre délai de vente.');
$metaDescription = (string) ($config['meta_description'] ?? ('Estimation gratuite à ' . $villePrincipale . ' et en Seine-et-Marne'));
$villes = $config['villes'] ?? [$villePrincipale, 'Savigny-le-Temple', 'Cesson', 'Vert-Saint-Denis', 'Moissy-Cramayel', 'Lieusaint', 'Saint-Pierre-du-Perray', 'Réau', 'Combs-la-Ville'];
if (!is_array($villes) || $villes === []) {
    $villes = [$villePrincipale, 'Savigny-le-Temple', 'Cesson', 'Vert-Saint-Denis', 'Moissy-Cramayel'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($agenceNom, ENT_QUOTES); ?> · Estimation immobilière à Nandy</title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-slate-900 antialiased">
    <main>
        <section id="hero" class="text-white" style="background: linear-gradient(135deg, <?= htmlspecialchars($couleur, ENT_QUOTES); ?>, #1d4ed8);">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 md:py-20 lg:px-8">
                <div class="mx-auto max-w-4xl text-center">
                    <p class="mx-auto inline-flex items-center gap-2 rounded-full border border-white/20 px-4 py-1.5 text-sm font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        <?= htmlspecialchars($villePrincipale, ENT_QUOTES); ?> et ses environs
                    </p>
                    <h1 class="mt-4 text-4xl font-bold sm:text-5xl lg:text-6xl"><?= htmlspecialchars($h1, ENT_QUOTES); ?></h1>
                    <p class="mt-6 text-lg sm:text-xl"><?= htmlspecialchars($sousTitre, ENT_QUOTES); ?></p>
                    <p class="mt-3 text-sm text-blue-100 sm:text-base">Même dans un marché plus sélectif, la demande est bien présente pour les biens correctement positionnés.</p>

                    <form id="estimation-form" class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="w-full lg:flex-1">
                            <label for="type_bien" class="mb-1 block text-sm font-medium text-blue-100">🏡 Type de bien</label>
                            <select id="type_bien" name="type_bien" required class="w-full rounded-xl border-0 bg-gray-50 px-4 py-4 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">Choisir</option>
                                <option value="Appartement">Appartement</option>
                                <option value="Maison">Maison</option>
                                <option value="Terrain">Terrain</option>
                                <option value="Local commercial">Local commercial</option>
                            </select>
                        </div>

                        <div class="w-full lg:flex-1">
                            <label for="ville" class="mb-1 block text-sm font-medium text-blue-100">📍 Ville</label>
                            <select id="ville" name="ville" required class="w-full rounded-xl border-0 bg-gray-50 px-4 py-4 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">Choisir</option>
                                <?php foreach ($villes as $ville): ?>
                                    <option value="<?= htmlspecialchars($ville, ENT_QUOTES); ?>"><?= htmlspecialchars($ville, ENT_QUOTES); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="w-full lg:flex-1 lg:px-3 lg:border-r lg:border-white/20">
                            <label for="surface_tranche" class="mb-1 block text-sm font-medium text-blue-100">📏 Surface</label>
                            <select id="surface_tranche" name="surface_tranche" required class="w-full rounded-xl border-0 bg-gray-50 px-4 py-4 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">Choisir</option>
                                <option value="lt30">Moins de 30 m²</option>
                                <option value="30_50">30-50 m²</option>
                                <option value="50_80">50-80 m²</option>
                                <option value="80_120">80-120 m²</option>
                                <option value="120_200">120-200 m²</option>
                                <option value="gt200">Plus de 200 m²</option>
                            </select>
                        </div>

                        <div class="w-full lg:flex-1">
                            <label for="budget_estime" class="mb-1 block text-sm font-medium text-blue-100">💰 Budget estimé</label>
                            <select id="budget_estime" name="budget_estime" required class="w-full rounded-xl border-0 bg-gray-50 px-4 py-4 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">Choisir</option>
                                <option value="lt100k">Moins de 100 000 €</option>
                                <option value="100_200k">100 000 - 200 000 €</option>
                                <option value="200_300k">200 000 - 300 000 €</option>
                                <option value="300_400k">300 000 - 400 000 €</option>
                                <option value="400_500k">400 000 - 500 000 €</option>
                                <option value="gt500k">Plus de 500 000 €</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-4">
                            <button type="submit" class="w-full rounded-xl bg-white px-4 py-4 font-semibold text-blue-700 transition hover:bg-gray-100">
                                Estimer mon bien →
                            </button>
                        </div>
                    </form>

                    <p id="form-feedback" class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700"></p>
                </div>
            </div>
        </section>

        <section id="result-section" class="hidden bg-gray-50 px-4 py-16 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl">
                <h2 class="text-center text-3xl font-bold text-slate-900">Votre estimation pour Nandy</h2>
                <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                        <div>
                            <p id="result-recap" class="text-sm text-slate-600"></p>
                            <p class="text-4xl font-bold text-blue-700">
                                <span id="result-min">0</span> - <span id="result-max">0</span> €
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-600">Prix au m² moyen</p>
                            <p id="result-price-m2" class="text-2xl font-bold text-blue-700">0 €/m²</p>
                        </div>
                    </div>
                    <div class="mt-6">
                        <input type="range" id="result-range" min="0" max="100" value="50" class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-slate-200">
                    </div>
                </div>

                <hr class="my-6 border-slate-200">

                <div id="result-workflow" class="space-y-4">
                    <p class="text-center text-sm text-slate-700">Pour affiner cette estimation, complétez ce court parcours.</p>
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div id="wizard-track" class="flex transition-transform duration-500 ease-out">
                            <div class="wizard-step w-full shrink-0 space-y-4 px-1">
                                <h3 class="text-center text-xl font-bold text-slate-900">Recevez votre rapport détaillé</h3>
                                <input id="rapport_email" name="email" type="email" placeholder="Votre email" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                <button id="step-email-next" type="button" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">Recevoir mon rapport →</button>
                            </div>
                            <div class="wizard-step w-full shrink-0 space-y-4 px-1">
                                <h3 class="text-center text-xl font-bold text-slate-900">Quel est votre projet ?</h3>
                                <div id="projet-pills" class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <label class="cursor-pointer rounded-xl border border-blue-200 px-3 py-4 text-center text-sm font-semibold text-blue-700 transition hover:border-blue-500 hover:bg-blue-50 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-600 has-[:checked]:text-white">
                                        <input type="radio" name="projet" value="Vendre mon bien" class="sr-only">
                                        🏠 Vendre
                                    </label>
                                    <label class="cursor-pointer rounded-xl border border-blue-200 px-3 py-4 text-center text-sm font-semibold text-blue-700 transition hover:border-blue-500 hover:bg-blue-50 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-600 has-[:checked]:text-white">
                                        <input type="radio" name="projet" value="Acheter un bien" class="sr-only">
                                        🔑 Acheter
                                    </label>
                                    <label class="cursor-pointer rounded-xl border border-blue-200 px-3 py-4 text-center text-sm font-semibold text-blue-700 transition hover:border-blue-500 hover:bg-blue-50 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-600 has-[:checked]:text-white">
                                        <input type="radio" name="projet" value="Louer mon bien" class="sr-only">
                                        📄 Louer
                                    </label>
                                </div>
                                <button id="step-projet-next" type="button" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">Suivant →</button>
                            </div>
                            <div class="wizard-step w-full shrink-0 space-y-4 px-1">
                                <h3 class="text-center text-xl font-bold text-slate-900">Comment souhaitez-vous vendre ?</h3>
                                <div id="methode-pills" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label class="cursor-pointer rounded-xl border border-blue-200 px-3 py-4 text-center text-sm font-semibold text-blue-700 transition hover:border-blue-500 hover:bg-blue-50 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-600 has-[:checked]:text-white">
                                        <input type="radio" name="methode_vente" value="Agence" class="sr-only">
                                        Via une agence
                                    </label>
                                    <label class="cursor-pointer rounded-xl border border-blue-200 px-3 py-4 text-center text-sm font-semibold text-blue-700 transition hover:border-blue-500 hover:bg-blue-50 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-600 has-[:checked]:text-white">
                                        <input type="radio" name="methode_vente" value="Particulier" class="sr-only">
                                        En particulier
                                    </label>
                                </div>
                                <button id="step-methode-next" type="button" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">Suivant →</button>
                            </div>
                            <div class="wizard-step w-full shrink-0 space-y-4 px-1">
                                <h3 class="text-center text-xl font-bold text-slate-900">Comment nous avez-vous connu ?</h3>
                                <select id="source_site" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                    <option value="">Choisir</option>
                                    <option value="Google">Google</option>
                                    <option value="Facebook">Facebook</option>
                                    <option value="Bouche à oreille">Bouche à oreille</option>
                                    <option value="Autre">Autre</option>
                                </select>
                                <button id="step-source-next" type="button" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">Suivant →</button>
                            </div>
                            <div class="wizard-step w-full shrink-0 space-y-4 px-1">
                                <h3 class="text-center text-xl font-bold text-slate-900">Votre situation</h3>
                                <select id="decisionnaire" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                    <option value="">Êtes-vous décisionnaire ?</option>
                                    <option value="Oui">Oui</option>
                                    <option value="Non">Non</option>
                                </select>
                                <select id="raison" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                    <option value="">Raison de votre projet</option>
                                    <option value="Changement de situation familiale">Changement de situation familiale</option>
                                    <option value="Investissement">Investissement</option>
                                    <option value="Déménagement professionnel">Déménagement professionnel</option>
                                    <option value="Autre">Autre</option>
                                </select>
                                <button id="step-situation-next" type="button" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">Suivant →</button>
                            </div>
                            <div class="wizard-step w-full shrink-0 space-y-4 px-1">
                                <h3 class="text-center text-xl font-bold text-slate-900">Votre timing</h3>
                                <select id="budget_bant" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                    <option value="">Budget validé ?</option>
                                    <option value="Oui">Oui</option>
                                    <option value="Non">Non</option>
                                </select>
                                <select id="delai" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                    <option value="">Délai de réalisation</option>
                                    <option value="Dans le mois">Dans le mois</option>
                                    <option value="Dans les 3 mois">Dans les 3 mois</option>
                                    <option value="Dans les 6 mois">Dans les 6 mois</option>
                                    <option value="Dans l'année">Dans l'année</option>
                                    <option value="Pas de délai précis">Pas de délai précis</option>
                                </select>
                                <button id="step-timing-next" type="button" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">Dernière étape →</button>
                            </div>
                            <form id="contact-form" class="wizard-step w-full shrink-0 space-y-3 px-1">
                                <h3 class="text-center text-xl font-bold text-slate-900">Vos coordonnées</h3>
                                <input id="prenom" name="prenom" type="text" placeholder="Prénom" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                <input id="telephone" name="telephone" type="tel" placeholder="Téléphone" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                <button id="contact-submit" type="submit" class="w-full rounded-xl bg-gradient-to-r from-blue-700 to-blue-500 px-4 py-3 font-bold text-white transition hover:from-blue-800 hover:to-blue-600">Me faire rappeler gratuitement →</button>
                            </form>
                        </div>
                        <div id="wizard-dots" class="mt-4 flex items-center justify-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        </div>
                        <p id="contact-feedback" class="mt-4 hidden rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700"></p>
                    </div>
                </div>
                <button id="new-estimation" type="button" class="mt-4 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    ← Nouvelle estimation
                </button>
            </div>
        </section>

        <section class="bg-gray-50 px-4 py-16 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <h2 class="text-center text-3xl font-bold text-slate-900">Comment ça marche</h2>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <article class="rounded-2xl bg-white p-6 shadow-sm">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full border-2 border-blue-600 text-sm font-bold text-blue-600">1</div>
                        <p class="text-2xl">📝</p>
                        <h3 class="mt-3 text-lg font-semibold">Décrivez votre bien</h3>
                        <p class="mt-2 text-sm text-slate-600">Renseignez les caractéristiques essentielles de votre bien pour une base de prix réaliste, adaptée à Nandy et ses environs.</p>
                    </article>
                    <article class="rounded-2xl bg-white p-6 shadow-sm">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full border-2 border-blue-600 text-sm font-bold text-blue-600">2</div>
                        <p class="text-2xl">⚡</p>
                        <h3 class="mt-3 text-lg font-semibold">Estimation stratégique</h3>
                        <p class="mt-2 text-sm text-slate-600">Recevez une fourchette cohérente avec la demande locale pour éviter la surévaluation, préserver l'attractivité de votre annonce et réduire les délais.</p>
                    </article>
                    <article class="rounded-2xl bg-white p-6 shadow-sm">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full border-2 border-blue-600 text-sm font-bold text-blue-600">3</div>
                        <p class="text-2xl">📞</p>
                        <h3 class="mt-3 text-lg font-semibold">Plan de mise en vente</h3>
                        <p class="mt-2 text-sm text-slate-600">Un conseiller affine votre estimation et vous aide à fixer le bon prix dès le lancement, pour vendre dans de meilleures conditions.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="px-4 py-16 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <h2 class="text-center text-3xl font-bold text-slate-900">Ils nous ont fait confiance</h2>
                <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <blockquote class="rounded-2xl bg-white p-6 shadow-sm">
                        <p class="text-sm text-slate-600">"On craignait un délai long. L'estimation nous a aidés à fixer le bon prix dès le début et nous avons reçu des visites qualifiées rapidement."</p>
                        <footer class="mt-3 text-xs font-semibold text-slate-500">— Jean, Nandy</footer>
                    </blockquote>
                    <blockquote class="rounded-2xl bg-white p-6 shadow-sm">
                        <p class="text-sm text-slate-600">"Très utile pour comprendre le marché local : l'outil nous a permis de positionner l'appartement au prix juste, sans brader."</p>
                        <footer class="mt-3 text-xs font-semibold text-slate-500">— Sophie, Savigny-le-Temple</footer>
                    </blockquote>
                    <blockquote class="rounded-2xl bg-white p-6 shadow-sm">
                        <p class="text-sm text-slate-600">"L'estimation ne m'a pas juste donné un chiffre : elle m'a donné une vraie stratégie de vente adaptée au secteur."</p>
                        <footer class="mt-3 text-xs font-semibold text-slate-500">— Marc, Cesson</footer>
                    </blockquote>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-6 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
        © <?= date('Y'); ?> · <?= htmlspecialchars($agenceNom, ENT_QUOTES); ?> · <a href="/pages/mentions-legales.php" class="hover:text-slate-600">Mentions légales</a> ·
        <a href="/pages/politique-confidentialite.php" class="hover:text-slate-600">Politique de confidentialité</a>
    </footer>

    <script>
        const form = document.getElementById('estimation-form');
        const feedback = document.getElementById('form-feedback');
        const resultSection = document.getElementById('result-section');
        const recap = document.getElementById('result-recap');
        const range = document.getElementById('result-range');
        const priceM2 = document.getElementById('result-price-m2');
        const newEstimationBtn = document.getElementById('new-estimation');
        const wizardTrack = document.getElementById('wizard-track');
        const wizardDots = [...document.querySelectorAll('#wizard-dots span')];
        const rapportEmail = document.getElementById('rapport_email');
        const stepEmailNext = document.getElementById('step-email-next');
        const stepProjetNext = document.getElementById('step-projet-next');
        const stepMethodeNext = document.getElementById('step-methode-next');
        const stepSourceNext = document.getElementById('step-source-next');
        const stepSituationNext = document.getElementById('step-situation-next');
        const stepTimingNext = document.getElementById('step-timing-next');
        const contactForm = document.getElementById('contact-form');
        const contactFeedback = document.getElementById('contact-feedback');
        const contactSubmit = document.getElementById('contact-submit');
        const projetPills = document.getElementById('projet-pills');
        const methodePills = document.getElementById('methode-pills');
        const sourceSite = document.getElementById('source_site');
        const decisionnaire = document.getElementById('decisionnaire');
        const raison = document.getElementById('raison');
        const budgetBant = document.getElementById('budget_bant');
        const delai = document.getElementById('delai');
        const emailRadios = [...projetPills.querySelectorAll('input[type="radio"]')];
        const methodeRadios = [...methodePills.querySelectorAll('input[type="radio"]')];
        let wizardStep = 0;
        let latestEstimation = {};

        function setWizardStep(step) {
            wizardStep = step;
            wizardTrack.style.transform = `translateX(-${step * 100}%)`;
            wizardDots.forEach((dot, index) => {
                dot.classList.toggle('bg-blue-600', index === step);
                dot.classList.toggle('bg-slate-300', index !== step);
            });
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price);
        }

        function getSurfaceValue(tranche) {
            const surfaces = {
                lt30: { min: 15, max: 30 },
                '30_50': { min: 30, max: 50 },
                '50_80': { min: 50, max: 80 },
                '80_120': { min: 80, max: 120 },
                '120_200': { min: 120, max: 200 },
                gt200: { min: 200, max: 300 }
            };
            return surfaces[tranche] || { min: 50, max: 80 };
        }

        function getBudgetValue(tranche) {
            const budgets = {
                lt100k: { min: 50000, max: 100000 },
                '100_200k': { min: 100000, max: 200000 },
                '200_300k': { min: 200000, max: 300000 },
                '300_400k': { min: 300000, max: 400000 },
                '400_500k': { min: 400000, max: 500000 },
                gt500k: { min: 500000, max: 1000000 }
            };
            return budgets[tranche] || { min: 200000, max: 300000 };
        }

        function calculateEstimation(typeBien, ville, surfaceTranche, budgetEstime) {
            // Prix moyens au m² pour Nandy et ses environs (à adapter selon les données réelles)
            const prixM2 = {
                'Appartement': {
                    'Nandy': 2800,
                    'Savigny-le-Temple': 3000,
                    'Cesson': 3100,
                    'Vert-Saint-Denis': 2900,
                    'Moissy-Cramayel': 2700,
                    'default': 2900
                },
                'Maison': {
                    'Nandy': 3200,
                    'Savigny-le-Temple': 3400,
                    'Cesson': 3500,
                    'Vert-Saint-Denis': 3300,
                    'Moissy-Cramayel': 3100,
                    'default': 3300
                },
                'Terrain': {
                    'Nandy': 200,
                    'Savigny-le-Temple': 220,
                    'Cesson': 230,
                    'Vert-Saint-Denis': 210,
                    'Moissy-Cramayel': 190,
                    'default': 210
                },
                'default': 3000
            };

            const surface = getSurfaceValue(surfaceTranche);
            const budget = getBudgetValue(budgetEstime);

            const prixMoyen = prixM2[typeBien]?.[ville] || prixM2[typeBien]?.default || prixM2.default;
            const prixMin = prixMoyen * 0.9 * surface.min;
            const prixMax = prixMoyen * 1.1 * surface.max;

            // Ajustement selon le budget estimé
            const budgetMin = budget.min;
            const budgetMax = budget.max;
            const estimationMin = Math.max(prixMin, budgetMin * 0.9);
            const estimationMax = Math.min(prixMax, budgetMax * 1.1);

            return {
                min: Math.round(estimationMin / 1000) * 1000,
                max: Math.round(estimationMax / 1000) * 1000,
                prixM2: prixMoyen,
                surfaceMin: surface.min,
                surfaceMax: surface.max
            };
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            feedback.classList.add('hidden');
            feedback.textContent = '';

            const submitButton = form.querySelector('button[type="submit"]');
            const buttonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Calcul en cours...';

            try {
                const typeBien = form.elements.type_bien.value;
                const ville = form.elements.ville.value;
                const surfaceTranche = form.elements.surface_tranche.value;
                const budgetEstime = form.elements.budget_estime.value;

                if (!typeBien || !ville || !surfaceTranche || !budgetEstime) {
                    throw new Error('Veuillez remplir tous les champs.');
                }

                latestEstimation = calculateEstimation(typeBien, ville, surfaceTranche, budgetEstime);

                recap.textContent = `${typeBien} de ${latestEstimation.surfaceMin}-${latestEstimation.surfaceMax} m² à ${ville}`;
                document.getElementById('result-min').textContent = latestEstimation.min.toLocaleString('fr-FR');
                document.getElementById('result-max').textContent = latestEstimation.max.toLocaleString('fr-FR');
                priceM2.textContent = `${latestEstimation.prixM2.toLocaleString('fr-FR')} €/m²`;

                resultSection.classList.remove('hidden');
                document.getElementById('hero').scrollIntoView({ behavior: 'smooth', block: 'start' });
                setWizardStep(0);
            } catch (error) {
                feedback.textContent = error.message;
                feedback.classList.remove('hidden');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = buttonText;
            }
        });

        newEstimationBtn.addEventListener('click', () => {
            form.reset();
            resultSection.classList.add('hidden');
            feedback.classList.add('hidden');
            setWizardStep(0);
        });

        projetPills.addEventListener('change', () => {
            [...projetPills.querySelectorAll('label')].forEach((label) => {
                const input = label.querySelector('input[type="radio"]');
                label.classList.toggle('ring-2', input.checked);
                label.classList.toggle('ring-blue-200', input.checked);
            });
            setWizardStep(2);
        });

        methodePills.addEventListener('change', () => {
            [...methodePills.querySelectorAll('label')].forEach((label) => {
                const input = label.querySelector('input[type="radio"]');
                label.classList.toggle('ring-2', input.checked);
                label.classList.toggle('ring-blue-200', input.checked);
            });
            setWizardStep(3);
        });

        stepEmailNext.addEventListener('click', async () => {
            if (!rapportEmail.reportValidity()) {
                return;
            }
            stepEmailNext.disabled = true;
            const originalText = stepEmailNext.textContent;
            stepEmailNext.textContent = 'Envoi...';
            try {
                const payload = new FormData();
                payload.append('email', rapportEmail.value.trim());
                const response = await fetch('/api/rapport.php', { method: 'POST', body: payload });
                if (!response.ok) {
                    throw new Error('Impossible d\'envoyer le rapport pour le moment.');
                }
                setWizardStep(1);
            } catch (error) {
                contactFeedback.className = 'mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700';
                contactFeedback.textContent = error.message || 'Service temporairement indisponible.';
                contactFeedback.classList.remove('hidden');
            } finally {
                stepEmailNext.disabled = false;
                stepEmailNext.textContent = originalText;
            }
        });

        stepProjetNext.addEventListener('click', () => {
            if (!emailRadios.some(input => input.checked)) {
                return;
            }
            setWizardStep(2);
        });

        stepMethodeNext.addEventListener('click', () => {
            if (!methodeRadios.some(input => input.checked)) {
                return;
            }
            setWizardStep(3);
        });

        stepSourceNext.addEventListener('click', () => {
            if (sourceSite.value === '') {
                sourceSite.reportValidity();
                return;
            }
            setWizardStep(4);
        });

        stepSituationNext.addEventListener('click', () => {
            if (decisionnaire.value === '' || raison.value === '') {
                if (decisionnaire.value === '') {
                    decisionnaire.reportValidity();
                } else {
                    raison.reportValidity();
                }
                return;
            }
            setWizardStep(5);
        });

        stepTimingNext.addEventListener('click', () => {
            if (budgetBant.value === '' || delai.value === '') {
                if (budgetBant.value === '') {
                    budgetBant.reportValidity();
                } else {
                    delai.reportValidity();
                }
                return;
            }
            setWizardStep(6);
        });

        contactForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            contactFeedback.classList.add('hidden');
            contactFeedback.textContent = '';
            setWizardStep(0);

            const submitButton = contactForm.querySelector('button[type="submit"]');
            const buttonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Envoi en cours...';

            try {
                const payload = new FormData(contactForm);
                payload.append('email', rapportEmail.value.trim());
                payload.append('projet', emailRadios.find((input) => input.checked)?.value || '');
                payload.append('methode_vente', methodeRadios.find((input) => input.checked)?.value || '');
                payload.append('source_site', sourceSite.value);
                payload.append('decisionnaire', decisionnaire.value);
                payload.append('raison', raison.value);
                payload.append('budget_bant', budgetBant.value);
                payload.append('delai', delai.value);
                Object.entries(latestEstimation).forEach(([key, value]) => payload.append(key, String(value)));

                const response = await fetch('/api/contact.php', {
                    method: 'POST',
                    body: payload
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Impossible d\'envoyer votre demande.');
                }

                const prenom = (payload.get('prenom') || '').toString().trim();
                contactFeedback.className = 'rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700 transition-all duration-500';
                contactFeedback.textContent = `✅ Merci ${prenom} ! Un conseiller vous rappelle sous 24h pour votre projet à Nandy.`;
                contactFeedback.classList.remove('hidden');
                contactFeedback.animate(
                    [{ transform: 'scale(0.96)', opacity: 0 }, { transform: 'scale(1)', opacity: 1 }],
                    { duration: 280, easing: 'ease-out' }
                );
                contactForm.reset();
                rapportEmail.value = '';
                projetPills.querySelectorAll('input').forEach((input) => {
                    input.checked = false;
                });
                methodePills.querySelectorAll('input').forEach((input) => {
                    input.checked = false;
                });
                sourceSite.value = '';
                decisionnaire.value = '';
                raison.value = '';
                budgetBant.value = '';
                delai.value = '';
                setWizardStep(0);
            } catch (error) {
                contactFeedback.className = 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700';
                contactFeedback.textContent = error.message || 'Le service est momentanément indisponible.';
                contactFeedback.classList.remove('hidden');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = buttonText;
            }
        });
    </script>
</body>
</html>
