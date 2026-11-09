/** PH location options (spec §3 lib/locations.js): grouped cities + rural municipalities. */

export const PH_CITIES = [
  { group: 'Mega Manila', options: [
    'Quezon City', 'Caloocan', 'Las Piñas', 'Makati', 'Malabon', 'Mandaluyong',
    'Marikina', 'Muntinlupa', 'Navotas', 'Parañaque', 'Pasay', 'Pasig',
    'San Juan', 'Taguig', 'Valenzuela', 'Manila',
  ]},
  { group: 'Luzon', options: [
    'Angeles', 'Antipolo', 'Bacoor', 'Baguio', 'Batangas City', 'Cabanatuan',
    'Calamba', 'Calasiao', 'Cauayan', 'Dagupan', 'Iligan', 'Laoag',
    'Legazpi', 'Lucena', 'Naga', 'Olongapo', 'Pangasinan', 'San Fernando (La Union)',
    'San Fernando (Pampanga)', 'San Jose del Monte', 'Santiago', 'Tarlac City', 'Tuguegarao',
  ]},
  { group: 'Visayas', options: [
    'Bacolod', 'Biliran', 'Cebu City', 'Danao', 'Dumaguete', 'Iloilo City',
    'Mandaue', 'Ormoc', 'Roxas', 'Tacloban', 'Tagbilaran', 'Talibon',
  ]},
  { group: 'Mindanao', options: [
    'Butuan', 'Cagayan de Oro', 'Cotabato', 'Davao City', 'Digos', 'Dipolog',
    'General Santos', 'Kidapawan', 'Koronadal', 'Marawi', 'Ozamiz', 'Pagadian',
    'Surigao City', 'Zamboanga City',
  ]},
]

export const PH_RURAL = [
  'Bakasyon Grande', 'Banig', 'Biuag', 'Buenavista', 'Bulan', 'Cabangan',
  'Cabugao', 'Calasiao', 'Dumalneg', 'Iguig', 'Lala', 'Lumicao',
  'Mabuhay', 'Magsingal', 'Nabunturan', 'Nabua', 'Pagsanjan', 'Pinamalayan',
  'Sagada', 'Salcedo', 'San Mateo', 'Santa Elena', 'Santa Teresita', 'Sico',
]

export const PH_LOCATIONS = [...PH_CITIES.flatMap((g) => g.options), ...PH_RURAL]

export default { PH_CITIES, PH_RURAL, PH_LOCATIONS }
