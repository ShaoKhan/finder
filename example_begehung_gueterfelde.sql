-- Beispiel-Begehung für Güterfelde, Potsdam-Mittelmark, Brandenburg
-- Fläche: 1000qm, Dauer: >2 Stunden, 3 Funde
-- Verwendet bestehenden Benutzer mit ID 1 und UUID: 049290dd-df2f-4cde-bceb-6ed969f3d305

-- Begehung mit GPS-Track um Güterfelde (Koordinaten: 52.3333, 13.1167)
INSERT INTO begehung (
    id,
    uuid,
    user_id,
    start_latitude,
    start_longitude,
    end_latitude,
    end_longitude,
    start_time,
    end_time,
    duration,
    polygon_data,
    is_active,
    created_at
) VALUES (
    4,
    '550e8400-e29b-41d4-a716-446655440004',
    1,
    52.333300,  -- Start: Güterfelde
    13.116700,
    52.333300,  -- Ende: zurück zum Startpunkt
    13.116700,
    '2024-01-25 09:00:00',  -- Startzeit
    '2024-01-25 12:30:00',  -- Endzeit (3h 30m)
    12600,  -- Dauer: 3h 30m in Sekunden
    '[
        {
            "latitude": 52.333300,
            "longitude": 13.116700,
            "timestamp": "2024-01-25T09:00:00+01:00"
        },
        {
            "latitude": 52.333315,
            "longitude": 13.116720,
            "timestamp": "2024-01-25T09:05:00+01:00"
        },
        {
            "latitude": 52.333285,
            "longitude": 13.116750,
            "timestamp": "2024-01-25T09:10:00+01:00"
        },
        {
            "latitude": 52.333310,
            "longitude": 13.116780,
            "timestamp": "2024-01-25T09:15:00+01:00"
        },
        {
            "latitude": 52.333295,
            "longitude": 13.116810,
            "timestamp": "2024-01-25T09:20:00+01:00"
        },
        {
            "latitude": 52.333325,
            "longitude": 13.116840,
            "timestamp": "2024-01-25T09:25:00+01:00"
        },
        {
            "latitude": 52.333280,
            "longitude": 13.116870,
            "timestamp": "2024-01-25T09:30:00+01:00"
        },
        {
            "latitude": 52.333320,
            "longitude": 13.116900,
            "timestamp": "2024-01-25T09:35:00+01:00"
        },
        {
            "latitude": 52.333290,
            "longitude": 13.116930,
            "timestamp": "2024-01-25T09:40:00+01:00"
        },
        {
            "latitude": 52.333305,
            "longitude": 13.116960,
            "timestamp": "2024-01-25T09:45:00+01:00"
        },
        {
            "latitude": 52.333275,
            "longitude": 13.116990,
            "timestamp": "2024-01-25T09:50:00+01:00"
        },
        {
            "latitude": 52.333330,
            "longitude": 13.117020,
            "timestamp": "2024-01-25T09:55:00+01:00"
        },
        {
            "latitude": 52.333285,
            "longitude": 13.117050,
            "timestamp": "2024-01-25T10:00:00+01:00"
        },
        {
            "latitude": 52.333315,
            "longitude": 13.117080,
            "timestamp": "2024-01-25T10:05:00+01:00"
        },
        {
            "latitude": 52.333300,
            "longitude": 13.117110,
            "timestamp": "2024-01-25T10:10:00+01:00"
        },
        {
            "latitude": 52.333320,
            "longitude": 13.117140,
            "timestamp": "2024-01-25T10:15:00+01:00"
        },
        {
            "latitude": 52.333290,
            "longitude": 13.117170,
            "timestamp": "2024-01-25T10:20:00+01:00"
        },
        {
            "latitude": 52.333310,
            "longitude": 13.117200,
            "timestamp": "2024-01-25T10:25:00+01:00"
        },
        {
            "latitude": 52.333275,
            "longitude": 13.117230,
            "timestamp": "2024-01-25T10:30:00+01:00"
        },
        {
            "latitude": 52.333325,
            "longitude": 13.117260,
            "timestamp": "2024-01-25T10:35:00+01:00"
        },
        {
            "latitude": 52.333295,
            "longitude": 13.117290,
            "timestamp": "2024-01-25T10:40:00+01:00"
        },
        {
            "latitude": 52.333320,
            "longitude": 13.117320,
            "timestamp": "2024-01-25T10:45:00+01:00"
        },
        {
            "latitude": 52.333280,
            "longitude": 13.117350,
            "timestamp": "2024-01-25T10:50:00+01:00"
        },
        {
            "latitude": 52.333310,
            "longitude": 13.117380,
            "timestamp": "2024-01-25T10:55:00+01:00"
        },
        {
            "latitude": 52.333300,
            "longitude": 13.117410,
            "timestamp": "2024-01-25T11:00:00+01:00"
        },
        {
            "latitude": 52.333315,
            "longitude": 13.117440,
            "timestamp": "2024-01-25T11:05:00+01:00"
        },
        {
            "latitude": 52.333285,
            "longitude": 13.117470,
            "timestamp": "2024-01-25T11:10:00+01:00"
        },
        {
            "latitude": 52.333330,
            "longitude": 13.117500,
            "timestamp": "2024-01-25T11:15:00+01:00"
        },
        {
            "latitude": 52.333290,
            "longitude": 13.117530,
            "timestamp": "2024-01-25T11:20:00+01:00"
        },
        {
            "latitude": 52.333320,
            "longitude": 13.117560,
            "timestamp": "2024-01-25T11:25:00+01:00"
        },
        {
            "latitude": 52.333275,
            "longitude": 13.117590,
            "timestamp": "2024-01-25T11:30:00+01:00"
        },
        {
            "latitude": 52.333310,
            "longitude": 13.117620,
            "timestamp": "2024-01-25T11:35:00+01:00"
        },
        {
            "latitude": 52.333300,
            "longitude": 13.117650,
            "timestamp": "2024-01-25T11:40:00+01:00"
        },
        {
            "latitude": 52.333315,
            "longitude": 13.117680,
            "timestamp": "2024-01-25T11:45:00+01:00"
        },
        {
            "latitude": 52.333285,
            "longitude": 13.117710,
            "timestamp": "2024-01-25T11:50:00+01:00"
        },
        {
            "latitude": 52.333325,
            "longitude": 13.117740,
            "timestamp": "2024-01-25T11:55:00+01:00"
        },
        {
            "latitude": 52.333290,
            "longitude": 13.117770,
            "timestamp": "2024-01-25T12:00:00+01:00"
        },
        {
            "latitude": 52.333320,
            "longitude": 13.117800,
            "timestamp": "2024-01-25T12:05:00+01:00"
        },
        {
            "latitude": 52.333300,
            "longitude": 13.117830,
            "timestamp": "2024-01-25T12:10:00+01:00"
        },
        {
            "latitude": 52.333310,
            "longitude": 13.117860,
            "timestamp": "2024-01-25T12:15:00+01:00"
        },
        {
            "latitude": 52.333295,
            "longitude": 13.117890,
            "timestamp": "2024-01-25T12:20:00+01:00"
        },
        {
            "latitude": 52.333325,
            "longitude": 13.117920,
            "timestamp": "2024-01-25T12:25:00+01:00"
        },
        {
            "latitude": 52.333300,
            "longitude": 13.116700,
            "timestamp": "2024-01-25T12:30:00+01:00"
        }
    ]',
    0,  -- nicht aktiv
    '2024-01-25 09:00:00'
);

-- 3 Funde für diese Begehung
-- Fund 1: Keramikscherbe
INSERT INTO founds_image (
    id,
    name,
    file_path,
    note,
    username,
    user_uuid,
    created_at,
    utm_x,
    utm_y,
    parcel,
    district,
    county,
    state,
    nearest_street,
    nearest_town,
    distance_to_church_or_center,
    church_or_center_name,
    direction_to_church_or_center,
    camera_model,
    exposure_time,
    f_number,
    iso,
    date_time,
    latitude,
    longitude,
    is_public,
    gemarkung_name,
    gemarkung_nummer,
    flurstueck_name,
    flurstueck_nummer,
    user_id,
    project_id,
    begehung_id,
    track_index
) VALUES (
    1,
    'Keramikscherbe mittelalterlich',
    '/fundbilder/keramik_001.jpg',
    'Mittelalterliche Keramikscherbe, grau-braun, ca. 3x4cm',
    'Max Mustermann',
    '049290dd-df2f-4cde-bceb-6ed969f3d305',
    '2024-01-25 09:15:00',
    333000.00,
    5800000.00,
    '123831001',
    'Güterfelde',
    'Potsdam-Mittelmark',
    'Brandenburg',
    'Dorfstraße',
    'Güterfelde',
    150.50,
    'Potsdam-Mittelmark',
    'NO',
    'Brandenburg',
    '1/125',
    'f/8',
    200,
    '2024-01-25 09:15:00',
    52.333310,
    13.116780,
    0,
    'Güterfelde',
    '123831',
    'Flur 001',
    '123831001',
    1,
    NULL,
    4,
    3
);

-- Fund 2: Eisenfragment
INSERT INTO founds_image (
    id,
    name,
    file_path,
    note,
    username,
    user_uuid,
    created_at,
    utm_x,
    utm_y,
    parcel,
    district,
    county,
    state,
    nearest_street,
    nearest_town,
    distance_to_church_or_center,
    church_or_center_name,
    direction_to_church_or_center,
    camera_model,
    exposure_time,
    f_number,
    iso,
    date_time,
    latitude,
    longitude,
    is_public,
    gemarkung_name,
    gemarkung_nummer,
    flurstueck_name,
    flurstueck_nummer,
    user_id,
    project_id,
    begehung_id,
    track_index
) VALUES (
    2,
    'Eisenfragment rostig',
    '/fundbilder/eisen_001.jpg',
    'Rostiges Eisenfragment, vermutlich Nagel oder Beschlag, ca. 2cm lang',
    'Max Mustermann',
    '049290dd-df2f-4cde-bceb-6ed969f3d305',
    '2024-01-25 10:30:00',
    333500.00,
    5800500.00,
    '123831001',
    'Güterfelde',
    'Potsdam-Mittelmark',
    'Brandenburg',
    'Feldweg',
    'Güterfelde',
    200.75,
    'Potsdam-Mittelmark',
    'O',
    'Brandenburg',
    '1/250',
    'f/5.6',
    400,
    '2024-01-25 10:30:00',
    52.333275,
    13.117230,
    0,
    'Güterfelde',
    '123831',
    'Flur 001',
    '123831001',
    1,
    NULL,
    4,
    15
);

-- Fund 3: Glasfragment
INSERT INTO founds_image (
    id,
    name,
    file_path,
    note,
    username,
    user_uuid,
    created_at,
    utm_x,
    utm_y,
    parcel,
    district,
    county,
    state,
    nearest_street,
    nearest_town,
    distance_to_church_or_center,
    church_or_center_name,
    direction_to_church_or_center,
    camera_model,
    exposure_time,
    f_number,
    iso,
    date_time,
    latitude,
    longitude,
    is_public,
    gemarkung_name,
    gemarkung_nummer,
    flurstueck_name,
    flurstueck_nummer,
    user_id,
    project_id,
    begehung_id,
    track_index
) VALUES (
    3,
    'Glasfragment grün',
    '/fundbilder/glas_001.jpg',
    'Grünes Glasfragment, vermutlich Flaschenglas, ca. 1.5x2cm',
    'Max Mustermann',
    '049290dd-df2f-4cde-bceb-6ed969f3d305',
    '2024-01-25 11:45:00',
    334000.00,
    5801000.00,
    '123831001',
    'Güterfelde',
    'Potsdam-Mittelmark',
    'Brandenburg',
    'Waldrand',
    'Güterfelde',
    300.25,
    'Potsdam-Mittelmark',
    'SO',
    'Brandenburg',
    '1/60',
    'f/4',
    800,
    '2024-01-25 11:45:00',
    52.333315,
    13.117680,
    0,
    'Güterfelde',
    '123831',
    'Flur 001',
    '123831001',
    1,
    NULL,
    4,
    30
);
