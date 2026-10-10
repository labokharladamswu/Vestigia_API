-- Vestigia schema for Supabase (SQL Editor -> New query -> paste -> Run)
-- Reconstructed from your pgAdmin dump (vestigia_db).

CREATE TABLE IF NOT EXISTS public.users (
    id              SERIAL PRIMARY KEY,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    middle_initial  VARCHAR(5),
    student_id      VARCHAR(50)  NOT NULL UNIQUE,
    contact_number  VARCHAR(30),
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    photo_path      VARCHAR(255),
    created_at      TIMESTAMP NOT NULL DEFAULT now(),
    is_admin        BOOLEAN   NOT NULL DEFAULT false
);

CREATE TABLE IF NOT EXISTS public.sessions (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    token       VARCHAR(255) NOT NULL UNIQUE,
    created_at  TIMESTAMP NOT NULL DEFAULT now(),
    expires_at  TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS public.found_items (
    id           SERIAL PRIMARY KEY,
    item_name    VARCHAR(150) NOT NULL,
    description  TEXT,
    category     VARCHAR(100),
    location     VARCHAR(150) NOT NULL,
    date_found   DATE NOT NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'Unclaimed',
    image_path   VARCHAR(255),
    reported_by  INTEGER REFERENCES public.users(id) ON DELETE SET NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT now(),
    updated_at   TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT found_items_status_check CHECK (status IN ('Unclaimed', 'Claimed'))
);

CREATE TABLE IF NOT EXISTS public.lost_items (
    id                  SERIAL PRIMARY KEY,
    item_name           VARCHAR(150) NOT NULL,
    description         TEXT,
    category            VARCHAR(100),
    last_seen_location  VARCHAR(150) NOT NULL,
    date_lost           DATE NOT NULL,
    status              VARCHAR(20) NOT NULL DEFAULT 'Missing',
    image_path          VARCHAR(255),
    reported_by         INTEGER REFERENCES public.users(id) ON DELETE SET NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT lost_items_status_check CHECK (status IN ('Missing', 'Found', 'Closed'))
);

CREATE TABLE IF NOT EXISTS public.claims (
    id                 SERIAL PRIMARY KEY,
    item_id            INTEGER NOT NULL REFERENCES public.found_items(id) ON DELETE CASCADE,
    claimed_by         INTEGER NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    proof_description  TEXT NOT NULL,
    proof_photo_path   VARCHAR(255),
    status             VARCHAR(20) NOT NULL DEFAULT 'Pending',
    claimed_at         TIMESTAMP NOT NULL DEFAULT now(),
    CONSTRAINT claims_status_check CHECK (status IN ('Pending', 'Approved', 'Rejected'))
);

CREATE INDEX IF NOT EXISTS idx_found_items_date   ON public.found_items (date_found);
CREATE INDEX IF NOT EXISTS idx_found_items_status ON public.found_items (status);
CREATE INDEX IF NOT EXISTS idx_lost_items_date    ON public.lost_items (date_lost);
CREATE INDEX IF NOT EXISTS idx_lost_items_status  ON public.lost_items (status);

-- SECURITY: Supabase exposes every public table through its REST API using the
-- public "anon" key. Turning RLS on with no policies blocks that route completely.
-- Your PHP API connects as the postgres role, which bypasses RLS, so it keeps working.
ALTER TABLE public.users       ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sessions    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.found_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.lost_items  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.claims      ENABLE ROW LEVEL SECURITY;

-- Make yourself an admin after registering through the app:
-- UPDATE public.users SET is_admin = true WHERE email = 'you@example.com';
