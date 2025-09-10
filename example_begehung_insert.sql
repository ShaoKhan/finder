-- Beispiel-Eintrag für eine Begehung mit mehreren GPS-Punkten
-- Koordinaten aus der Region Brandenburg (Potsdam/Teltow)
-- Verwendet bestehenden Benutzer mit ID 1 und UUID: 049290dd-df2f-4cde-bceb-6ed969f3d305

-- Beispiel-Begehung mit mehreren GPS-Punkten
-- Route: Potsdam Stadtzentrum -> Teltow -> Kleinmachnow -> zurück nach Potsdam
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
    1,
    '550e8400-e29b-41d4-a716-446655440001',
    1,
    52.398860,  -- Start: Potsdam, Brandenburger Straße
    13.065660,
    52.398860,  -- Ende: zurück zum Startpunkt
    13.065660,
    '2024-01-20 08:30:00',  -- Startzeit
    '2024-01-20 11:45:00',  -- Endzeit
    11700,  -- Dauer: 3h 15m in Sekunden
    '[
        {
            "latitude": 52.398860,
            "longitude": 13.065660,
            "timestamp": "2024-01-20T08:30:00+01:00"
        },
        {
            "latitude": 52.400120,
            "longitude": 13.067890,
            "timestamp": "2024-01-20T08:35:00+01:00"
        },
        {
            "latitude": 52.401500,
            "longitude": 13.070200,
            "timestamp": "2024-01-20T08:40:00+01:00"
        },
        {
            "latitude": 52.403200,
            "longitude": 13.072500,
            "timestamp": "2024-01-20T08:45:00+01:00"
        },
        {
            "latitude": 52.405000,
            "longitude": 13.075000,
            "timestamp": "2024-01-20T08:50:00+01:00"
        },
        {
            "latitude": 52.407500,
            "longitude": 13.078000,
            "timestamp": "2024-01-20T08:55:00+01:00"
        },
        {
            "latitude": 52.410000,
            "longitude": 13.081000,
            "timestamp": "2024-01-20T09:00:00+01:00"
        },
        {
            "latitude": 52.412500,
            "longitude": 13.084000,
            "timestamp": "2024-01-20T09:05:00+01:00"
        },
        {
            "latitude": 52.415000,
            "longitude": 13.087000,
            "timestamp": "2024-01-20T09:10:00+01:00"
        },
        {
            "latitude": 52.417500,
            "longitude": 13.090000,
            "timestamp": "2024-01-20T09:15:00+01:00"
        },
        {
            "latitude": 52.420000,
            "longitude": 13.093000,
            "timestamp": "2024-01-20T09:20:00+01:00"
        },
        {
            "latitude": 52.422500,
            "longitude": 13.096000,
            "timestamp": "2024-01-20T09:25:00+01:00"
        },
        {
            "latitude": 52.425000,
            "longitude": 13.099000,
            "timestamp": "2024-01-20T09:30:00+01:00"
        },
        {
            "latitude": 52.427500,
            "longitude": 13.102000,
            "timestamp": "2024-01-20T09:35:00+01:00"
        },
        {
            "latitude": 52.430000,
            "longitude": 13.105000,
            "timestamp": "2024-01-20T09:40:00+01:00"
        },
        {
            "latitude": 52.432500,
            "longitude": 13.108000,
            "timestamp": "2024-01-20T09:45:00+01:00"
        },
        {
            "latitude": 52.435000,
            "longitude": 13.111000,
            "timestamp": "2024-01-20T09:50:00+01:00"
        },
        {
            "latitude": 52.437500,
            "longitude": 13.114000,
            "timestamp": "2024-01-20T09:55:00+01:00"
        },
        {
            "latitude": 52.440000,
            "longitude": 13.117000,
            "timestamp": "2024-01-20T10:00:00+01:00"
        },
        {
            "latitude": 52.442500,
            "longitude": 13.120000,
            "timestamp": "2024-01-20T10:05:00+01:00"
        },
        {
            "latitude": 52.445000,
            "longitude": 13.123000,
            "timestamp": "2024-01-20T10:10:00+01:00"
        },
        {
            "latitude": 52.447500,
            "longitude": 13.126000,
            "timestamp": "2024-01-20T10:15:00+01:00"
        },
        {
            "latitude": 52.450000,
            "longitude": 13.129000,
            "timestamp": "2024-01-20T10:20:00+01:00"
        },
        {
            "latitude": 52.452500,
            "longitude": 13.132000,
            "timestamp": "2024-01-20T10:25:00+01:00"
        },
        {
            "latitude": 52.455000,
            "longitude": 13.135000,
            "timestamp": "2024-01-20T10:30:00+01:00"
        },
        {
            "latitude": 52.457500,
            "longitude": 13.138000,
            "timestamp": "2024-01-20T10:35:00+01:00"
        },
        {
            "latitude": 52.460000,
            "longitude": 13.141000,
            "timestamp": "2024-01-20T10:40:00+01:00"
        },
        {
            "latitude": 52.462500,
            "longitude": 13.144000,
            "timestamp": "2024-01-20T10:45:00+01:00"
        },
        {
            "latitude": 52.465000,
            "longitude": 13.147000,
            "timestamp": "2024-01-20T10:50:00+01:00"
        },
        {
            "latitude": 52.467500,
            "longitude": 13.150000,
            "timestamp": "2024-01-20T10:55:00+01:00"
        },
        {
            "latitude": 52.470000,
            "longitude": 13.153000,
            "timestamp": "2024-01-20T11:00:00+01:00"
        },
        {
            "latitude": 52.472500,
            "longitude": 13.156000,
            "timestamp": "2024-01-20T11:05:00+01:00"
        },
        {
            "latitude": 52.475000,
            "longitude": 13.159000,
            "timestamp": "2024-01-20T11:10:00+01:00"
        },
        {
            "latitude": 52.477500,
            "longitude": 13.162000,
            "timestamp": "2024-01-20T11:15:00+01:00"
        },
        {
            "latitude": 52.480000,
            "longitude": 13.165000,
            "timestamp": "2024-01-20T11:20:00+01:00"
        },
        {
            "latitude": 52.482500,
            "longitude": 13.168000,
            "timestamp": "2024-01-20T11:25:00+01:00"
        },
        {
            "latitude": 52.485000,
            "longitude": 13.171000,
            "timestamp": "2024-01-20T11:30:00+01:00"
        },
        {
            "latitude": 52.487500,
            "longitude": 13.174000,
            "timestamp": "2024-01-20T11:35:00+01:00"
        },
        {
            "latitude": 52.490000,
            "longitude": 13.177000,
            "timestamp": "2024-01-20T11:40:00+01:00"
        },
        {
            "latitude": 52.492500,
            "longitude": 13.180000,
            "timestamp": "2024-01-20T11:45:00+01:00"
        },
        {
            "latitude": 52.398860,
            "longitude": 13.065660,
            "timestamp": "2024-01-20T11:45:00+01:00"
        }
    ]',
    0,  -- nicht aktiv
    '2024-01-20 08:30:00'
);

-- Zusätzlicher Beispiel-Eintrag mit weniger Punkten (einfache Route)
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
    2,
    '550e8400-e29b-41d4-a716-446655440002',
    1,
    52.398860,  -- Start: Potsdam
    13.065660,
    52.400000,  -- Ende: Teltow
    13.080000,
    '2024-01-21 14:00:00',
    '2024-01-21 15:30:00',
    5400,  -- Dauer: 1h 30m
    '[
        {
            "latitude": 52.398860,
            "longitude": 13.065660,
            "timestamp": "2024-01-21T14:00:00+01:00"
        },
        {
            "latitude": 52.399500,
            "longitude": 13.070000,
            "timestamp": "2024-01-21T14:15:00+01:00"
        },
        {
            "latitude": 52.400200,
            "longitude": 13.075000,
            "timestamp": "2024-01-21T14:30:00+01:00"
        },
        {
            "latitude": 52.400500,
            "longitude": 13.077500,
            "timestamp": "2024-01-21T14:45:00+01:00"
        },
        {
            "latitude": 52.400000,
            "longitude": 13.080000,
            "timestamp": "2024-01-21T15:30:00+01:00"
        }
    ]',
    0,
    '2024-01-21 14:00:00'
);

-- Beispiel für eine aktive Begehung (noch nicht beendet)
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
    3,
    '550e8400-e29b-41d4-a716-446655440003',
    1,
    52.398860,
    13.065660,
    NULL,  -- noch nicht beendet
    NULL,
    '2024-01-22 09:00:00',
    NULL,  -- noch nicht beendet
    NULL,  -- noch nicht berechnet
    '[
        {
            "latitude": 52.398860,
            "longitude": 13.065660,
            "timestamp": "2024-01-22T09:00:00+01:00"
        },
        {
            "latitude": 52.399200,
            "longitude": 13.067000,
            "timestamp": "2024-01-22T09:05:00+01:00"
        },
        {
            "latitude": 52.399500,
            "longitude": 13.068500,
            "timestamp": "2024-01-22T09:10:00+01:00"
        }
    ]',
    1,  -- aktiv
    '2024-01-22 09:00:00'
);
