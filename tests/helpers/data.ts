import * as path from 'path';

const FIXTURES_DIR = path.join(__dirname, '../../fixtures');

export interface CasinoData {
  title: string;
  affiliateLink: string;
  yearFounded: string;
  trustpilotScore: string;
  overallRating: string;
  ratingBonus: string;
  ratingGames: string;
  ratingPayments: string;
  ratingSupport: string;
  ratingReliability: string;
  welcomeBonusText: string;
  wagering: string;
  minDeposit: string;
  noDepositBonus: string;
  freeSpins: string;
  promoCode: string;
  license: string;
  licenseNumber: string;
  gamesCount: string;
  withdrawalTimeMin: string;
  withdrawalTimeMax: string;
  providers: string[];
  depositMethods: string[];
  pros: string[];
  cons: string[];
  introText: string;
  summary1Title: string;
  summary1: string;
  finalVerdict: string;
  seoTitle: string;
  metaDescription: string;
  logoFixture: string;
  taxonomies: {
    casino_license: string[];
    casino_feature: string[];
    payment_method: string[];
    game_type: string[];
  };
}

export const CASINOS: CasinoData[] = [
  {
    title: 'PW Lunara Casino',
    affiliateLink: 'https://example.com/go/pw-lunara',
    yearFounded: '2019',
    trustpilotScore: '4.2',
    overallRating: '8.5',
    ratingBonus: '9.0',
    ratingGames: '8.5',
    ratingPayments: '8.0',
    ratingSupport: '7.5',
    ratingReliability: '8.5',
    welcomeBonusText: '200% jusqu\'à 500€ + 100 tours gratuits',
    wagering: 'x35',
    minDeposit: '20',
    noDepositBonus: '10 tours gratuits sans dépôt',
    freeSpins: '100',
    promoCode: 'LUNARA100',
    license: 'MGA',
    licenseNumber: 'MGA/B2C/394/2017',
    gamesCount: '2500',
    withdrawalTimeMin: '24h',
    withdrawalTimeMax: '72h',
    providers: ['NetEnt', 'Microgaming', 'Play\'n GO'],
    depositMethods: ['Visa', 'Mastercard', 'Skrill'],
    pros: ['Grande sélection de jeux', 'Bonus généreux', 'Support 24/7'],
    cons: ['Exigences de mise élevées', 'Délais de retrait variables'],
    introText: 'Lunara Casino est une plateforme de jeux en ligne reconnue pour sa vaste bibliothèque de jeux et ses bonus attrayants. Fondé en 2019, il offre une expérience sécurisée sous licence MGA.',
    summary1Title: 'Bonus et promotions',
    summary1: '<p>Lunara Casino propose un bonus de bienvenue généreux de 200% jusqu\'à 500€ accompagné de 100 tours gratuits. Les conditions de mise sont de x35, ce qui est raisonnable pour le secteur.</p>',
    finalVerdict: '<p>Lunara Casino est un choix solide pour les joueurs recherchant une grande sélection de jeux et des bonus compétitifs. Notre note globale : 8.5/10.</p>',
    seoTitle: 'PW Lunara Casino Avis 2024 — Bonus, Jeux & Fiabilité',
    metaDescription: 'Découvrez notre avis complet sur PW Lunara Casino : bonus, jeux, paiements et fiabilité. Note globale : 8.5/10.',
    logoFixture: path.join(FIXTURES_DIR, 'logo-1.png'),
    taxonomies: {
      casino_license: ['MGA'],
      casino_feature: ['Live Casino', 'Mobile'],
      payment_method: ['Visa', 'Skrill'],
      game_type: ['Slots', 'Live Dealer'],
    },
  },
  {
    title: 'PW NovaJackpot',
    affiliateLink: 'https://example.com/go/pw-novajackpot',
    yearFounded: '2020',
    trustpilotScore: '3.9',
    overallRating: '7.8',
    ratingBonus: '8.5',
    ratingGames: '7.5',
    ratingPayments: '7.0',
    ratingSupport: '8.0',
    ratingReliability: '8.0',
    welcomeBonusText: '150% jusqu\'à 300€ + 50 tours gratuits',
    wagering: 'x40',
    minDeposit: '15',
    noDepositBonus: '',
    freeSpins: '50',
    promoCode: 'NOVA50',
    license: 'Curacao',
    licenseNumber: 'CGRI/2020/001',
    gamesCount: '1800',
    withdrawalTimeMin: '48h',
    withdrawalTimeMax: '96h',
    providers: ['Pragmatic Play', 'Evolution', 'Yggdrasil'],
    depositMethods: ['Visa', 'Bitcoin', 'PayPal'],
    pros: ['Bonus sans dépôt disponibles', 'Jackpots progressifs'],
    cons: ['Exigences de mise élevées', 'Service client limité'],
    introText: 'NovaJackpot se spécialise dans les jackpots progressifs et offre une expérience de jeu palpitante. Fondé en 2020, ce casino attire les amateurs de grandes gains.',
    summary1Title: 'Jeux disponibles',
    summary1: '<p>NovaJackpot propose plus de 1800 jeux incluant des machines à sous, des jeux de table et un casino en direct avec des croupiers professionnels.</p>',
    finalVerdict: '<p>NovaJackpot est idéal pour les chasseurs de jackpots progressifs. Notre note globale : 7.8/10.</p>',
    seoTitle: 'PW NovaJackpot Avis 2024 — Jackpots, Bonus & Fiabilité',
    metaDescription: 'Avis complet sur PW NovaJackpot : jackpots progressifs, bonus et paiements. Note : 7.8/10.',
    logoFixture: path.join(FIXTURES_DIR, 'logo-2.png'),
    taxonomies: {
      casino_license: ['Curacao'],
      casino_feature: ['Live Casino', 'VIP'],
      payment_method: ['Bitcoin', 'PayPal'],
      game_type: ['Slots', 'Roulette'],
    },
  },
  {
    title: 'PW HexaSpin',
    affiliateLink: 'https://example.com/go/pw-hexaspin',
    yearFounded: '2021',
    trustpilotScore: '4.5',
    overallRating: '9.0',
    ratingBonus: '9.5',
    ratingGames: '9.0',
    ratingPayments: '9.0',
    ratingSupport: '8.5',
    ratingReliability: '9.0',
    welcomeBonusText: '100% jusqu\'à 1000€ + 200 tours gratuits',
    wagering: 'x30',
    minDeposit: '10',
    noDepositBonus: '20 tours gratuits à l\'inscription',
    freeSpins: '200',
    promoCode: '',
    license: 'MGA',
    licenseNumber: 'MGA/B2C/519/2021',
    gamesCount: '3200',
    withdrawalTimeMin: '24h',
    withdrawalTimeMax: '48h',
    providers: ['NetEnt', 'Play\'n GO', 'Pragmatic Play', 'Red Tiger'],
    depositMethods: ['Visa', 'Mastercard', 'Skrill', 'PayPal'],
    pros: ['Wagering parmi les plus bas', 'Retraits rapides', 'Excellent support'],
    cons: ['Disponible uniquement en Europe', 'App mobile en développement'],
    introText: 'HexaSpin est l\'un des casinos les mieux notés de notre plateforme. Avec un bonus de bienvenue exceptionnel et des conditions de mise parmi les plus favorables du marché, il s\'adresse aux joueurs exigeants.',
    summary1Title: 'Bonus et offres de bienvenue',
    summary1: '<p>HexaSpin offre un bonus de bienvenue de 100% jusqu\'à 1000€ accompagné de 200 tours gratuits. Avec des conditions de mise de seulement x30, c\'est l\'une des offres les plus compétitives du marché.</p>',
    finalVerdict: '<p>HexaSpin est notre top pick pour les joueurs européens cherchant le meilleur rapport qualité/prix. Notre note : 9.0/10.</p>',
    seoTitle: 'PW HexaSpin Avis 2024 — Meilleur Casino en Ligne',
    metaDescription: 'Avis HexaSpin : notre meilleure note en 2024. Bonus 1000€, 200 tours gratuits, wagering x30. Lire notre avis complet.',
    logoFixture: path.join(FIXTURES_DIR, 'logo-3.png'),
    taxonomies: {
      casino_license: ['MGA'],
      casino_feature: ['Live Casino', 'Mobile', 'No Deposit'],
      payment_method: ['Visa', 'Skrill', 'PayPal'],
      game_type: ['Slots', 'Blackjack', 'Live Dealer'],
    },
  },
];

export interface SubpagePublishData {
  casinoTitle: string;
  subpageType: 'bonus' | 'retrait';
  heroTitle: string;
  introText: string;
  seoTitle: string;
  metaDescription: string;
  scoreEnabled: boolean;
  scoreValue?: string;
  scoreLabel?: string;
}

export function getSubpagesToPublish(): SubpagePublishData[] {
  const result: SubpagePublishData[] = [];

  for (const casino of CASINOS) {
    const casinoName = casino.title.replace('PW ', '');

    result.push({
      casinoTitle: casino.title,
      subpageType: 'bonus',
      heroTitle: `Bonus ${casinoName} — Offres et Promotions`,
      introText: `Découvrez toutes les offres bonus disponibles chez ${casinoName}. Bonus de bienvenue, tours gratuits et promotions régulières sont au programme.`,
      seoTitle: `Bonus ${casinoName} 2024 — Offre complète`,
      metaDescription: `Guide complet des bonus ${casinoName} : bienvenue, rechargement, tours gratuits. Tout ce que vous devez savoir.`,
      scoreEnabled: true,
      scoreValue: casino.ratingBonus,
      scoreLabel: 'Note Bonus',
    });

    result.push({
      casinoTitle: casino.title,
      subpageType: 'retrait',
      heroTitle: `Retrait ${casinoName} — Délais et Méthodes`,
      introText: `Tout ce que vous devez savoir sur les retraits chez ${casinoName}. Délais, méthodes de paiement et limites expliqués clairement.`,
      seoTitle: `Retrait ${casinoName} 2024 — Guide complet`,
      metaDescription: `Comment effectuer un retrait chez ${casinoName} ? Délais, méthodes et limites. Notre guide complet.`,
      scoreEnabled: false,
    });
  }

  return result;
}

export interface HubLandingData {
  title: string;
  heroTitle: string;
  introText: string;
  educationalContent: string;
  howtoContent: string;
  seoTitle: string;
  metaDescription: string;
  subcategoryCards: Array<{ title: string; url: string; description: string; icon: string }>;
}

export const HUB_LANDING: HubLandingData = {
  title: 'PW Casino En Ligne',
  heroTitle: 'Les Meilleurs Casinos en Ligne — Comparatif 2024',
  introText: 'Notre équipe d\'experts a testé et évalué les meilleurs casinos en ligne pour vous proposer un comparatif objectif et complet. Trouvez le casino qui correspond à vos besoins.',
  educationalContent: '<h2>Comment choisir un casino en ligne sûr ?</h2><p>Un casino en ligne fiable doit posséder une licence valide, protéger vos données personnelles et proposer des méthodes de paiement sécurisées.</p>',
  howtoContent: '<ol><li>Comparez les bonus de bienvenue</li><li>Vérifiez la licence et la réputation</li><li>Testez les méthodes de paiement</li><li>Évaluez la qualité du support</li></ol>',
  seoTitle: 'Meilleurs Casinos en Ligne 2024 — Comparatif complet',
  metaDescription: 'Comparez les meilleurs casinos en ligne en 2024. Bonus, jeux, paiements et fiabilité analysés par nos experts.',
  subcategoryCards: [
    { title: 'Casino Bonus', url: '/casino-bonus/', description: 'Meilleures offres bonus', icon: 'bonus' },
    { title: 'Casino Mobile', url: '/casino-mobile/', description: 'Jouer sur smartphone', icon: 'mobile' },
    { title: 'Casino Live', url: '/casino-live/', description: 'Jeux avec croupiers en direct', icon: 'live' },
  ],
};

export interface ComparisonLandingData {
  title: string;
  heroTitle: string;
  introText: string;
  lastUpdated: string;
  authorName: string;
  casinosTested: string;
  methodologyContent: string;
  bottomContent: string;
  seoTitle: string;
  metaDescription: string;
}

export const COMPARISON_LANDING: ComparisonLandingData = {
  title: 'PW Comparatif Casinos Bonus',
  heroTitle: 'Comparatif des Casinos avec les Meilleurs Bonus 2024',
  introText: 'Vous cherchez le meilleur bonus casino ? Notre équipe a comparé et testé les offres de plus de 50 casinos pour vous présenter les plus avantageuses.',
  lastUpdated: '2024-03-26',
  authorName: 'Équipe Casino Compare',
  casinosTested: '50',
  methodologyContent: '<h2>Notre méthodologie</h2><p>Chaque casino est évalué selon 5 critères : bonus, jeux, paiements, support et fiabilité. Nous testons personnellement chaque plateforme avant de publier notre avis.</p>',
  bottomContent: '<h2>Comment profiter au mieux des bonus casino ?</h2><p>Pour maximiser vos bonus, lisez attentivement les conditions de mise, jouez sur les jeux éligibles et respectez les délais d\'utilisation.</p>',
  seoTitle: 'Comparatif Casino Bonus 2024 — Meilleures Offres',
  metaDescription: 'Comparatif des meilleurs casinos avec bonus en 2024. Analyses détaillées, conditions de mise et notes d\'experts.',
};

export interface TrustLandingData {
  title: string;
  heroTitle: string;
  introText: string;
  pageContent: string;
  authorName: string;
  lastUpdated: string;
  seoTitle: string;
  metaDescription: string;
}

export const TRUST_LANDING: TrustLandingData = {
  title: 'PW Notre Méthodologie',
  heroTitle: 'Notre Méthodologie d\'Évaluation des Casinos',
  introText: 'Chez Casino Compare, nous croyons en la transparence totale. Découvrez comment nous évaluons les casinos en ligne et pourquoi vous pouvez faire confiance à nos avis.',
  pageContent: '<h2>Notre processus d\'évaluation</h2><p>Chaque casino est testé pendant minimum 30 jours par notre équipe d\'experts indépendants. Nous vérifions la licence, effectuons des dépôts et retraits réels, et testons le service client.</p><h2>Nos critères de notation</h2><ul><li>Sécurité et licence (20%)</li><li>Bonus et promotions (20%)</li><li>Sélection de jeux (20%)</li><li>Méthodes de paiement (20%)</li><li>Service client (20%)</li></ul>',
  authorName: 'Équipe Casino Compare',
  lastUpdated: '2024-03-26',
  seoTitle: 'Notre Méthodologie — Comment nous évaluons les casinos',
  metaDescription: 'Découvrez comment Casino Compare évalue les casinos en ligne. Processus transparent, critères objectifs et tests réels.',
};

export interface GuideData {
  title: string;
  category: string;
  readingTime: string;
  lastUpdated: string;
  authorName: string;
  introText: string;
  calloutText: string;
  mainContent: string;
  sidebarTakeaway: string;
  seoTitle: string;
  metaDescription: string;
  moneyPageLinks: Array<{ label: string; url: string }>;
  faq: Array<{ question: string; answer: string }>;
}

export const GUIDE: GuideData = {
  title: 'PW Comprendre le Wager',
  category: 'Bonus',
  readingTime: '8',
  lastUpdated: '2024-03-26',
  authorName: 'Jean Dupont',
  introText: 'Le wager (ou exigence de mise) est l\'une des conditions les plus importantes à comprendre avant d\'accepter un bonus casino. Ce guide vous explique tout ce que vous devez savoir.',
  calloutText: '⚠️ Vérifiez toujours le wager avant d\'accepter un bonus ! Un wager x50 signifie que vous devez miser 50 fois la valeur du bonus avant de pouvoir retirer vos gains.',
  mainContent: '<h2>Qu\'est-ce que le wager ?</h2><p>Le wager, également appelé mise minimale ou roulement, représente le nombre de fois que vous devez miser votre bonus avant de pouvoir effectuer un retrait.</p><h2>Comment calculer le wager ?</h2><p>Si vous recevez un bonus de 100€ avec un wager x35, vous devez miser 100€ × 35 = 3500€ avant de pouvoir retirer.</p><h2>Quels jeux comptent pour le wager ?</h2><p>La plupart du temps, les machines à sous contribuent à 100% au wager, tandis que les jeux de table ne contribuent qu\'à 10-20%.</p>',
  sidebarTakeaway: '<ul><li>Wager x30 ou moins = excellent</li><li>Wager x31-x40 = correct</li><li>Wager x40+ = exigeant</li></ul>',
  seoTitle: 'Comprendre le Wager Casino — Guide complet 2024',
  metaDescription: 'Qu\'est-ce que le wager casino ? Apprenez à calculer et comparer les exigences de mise pour choisir les meilleurs bonus.',
  moneyPageLinks: [
    { label: 'Meilleurs bonus casino', url: '/casino-bonus/' },
    { label: 'Comparatif casinos', url: '/casinos/' },
  ],
  faq: [
    { question: 'Qu\'est-ce que le wager ?', answer: 'Le wager est le nombre de fois que vous devez miser votre bonus avant de pouvoir retirer vos gains.' },
    { question: 'Un bon wager c\'est quoi ?', answer: 'Un wager de x30 ou moins est considéré comme excellent. Au-delà de x40, les conditions deviennent difficiles à remplir.' },
    { question: 'Est-ce qu\'on peut retirer sans avoir rempli le wager ?', answer: 'Non, vous devez obligatoirement remplir les conditions de mise avant tout retrait des gains issus du bonus.' },
  ],
};
