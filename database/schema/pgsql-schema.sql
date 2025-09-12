--
-- PostgreSQL database dump
--

-- Dumped from database version 17.5
-- Dumped by pg_dump version 17.5

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

-- *not* creating schema, since initdb creates it


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: children; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.children (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    age integer NOT NULL,
    independence_level integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: children_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.children_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: children_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.children_id_seq OWNED BY public.children.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: flashcard_imports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.flashcard_imports (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    user_id bigint NOT NULL,
    import_type character varying(255) NOT NULL,
    filename character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    total_cards integer DEFAULT 0 NOT NULL,
    imported_cards integer DEFAULT 0 NOT NULL,
    failed_cards integer DEFAULT 0 NOT NULL,
    duplicate_cards integer DEFAULT 0 NOT NULL,
    media_files integer DEFAULT 0 NOT NULL,
    import_options json,
    import_metadata json,
    import_results json,
    rollback_data json,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: flashcard_imports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.flashcard_imports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: flashcard_imports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.flashcard_imports_id_seq OWNED BY public.flashcard_imports.id;


--
-- Name: flashcards; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.flashcards (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    card_type character varying(255) DEFAULT 'basic'::character varying NOT NULL,
    question text NOT NULL,
    answer text NOT NULL,
    hint text,
    choices json,
    correct_choices json,
    cloze_text text,
    cloze_answers json,
    question_image_url character varying(255),
    answer_image_url character varying(255),
    occlusion_data json,
    difficulty_level character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    tags json,
    is_active boolean DEFAULT true NOT NULL,
    import_source character varying(50),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT flashcards_card_type_check CHECK (((card_type)::text = ANY ((ARRAY['basic'::character varying, 'multiple_choice'::character varying, 'true_false'::character varying, 'cloze'::character varying, 'typed_answer'::character varying, 'image_occlusion'::character varying])::text[]))),
    CONSTRAINT flashcards_difficulty_level_check CHECK (((difficulty_level)::text = ANY ((ARRAY['easy'::character varying, 'medium'::character varying, 'hard'::character varying])::text[])))
);


--
-- Name: flashcards_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.flashcards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: flashcards_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.flashcards_id_seq OWNED BY public.flashcards.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: kids_mode_audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kids_mode_audit_logs (
    id bigint NOT NULL,
    user_id character varying(255) NOT NULL,
    child_id integer,
    action character varying(255) NOT NULL,
    ip_address character varying(255),
    user_agent character varying(255),
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: kids_mode_audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.kids_mode_audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: kids_mode_audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.kids_mode_audit_logs_id_seq OWNED BY public.kids_mode_audit_logs.id;


--
-- Name: learning_sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.learning_sessions (
    id bigint NOT NULL,
    topic_id bigint NOT NULL,
    child_id bigint NOT NULL,
    estimated_minutes integer NOT NULL,
    status character varying(255) DEFAULT 'backlog'::character varying NOT NULL,
    scheduled_day_of_week integer,
    scheduled_start_time time(0) without time zone,
    scheduled_end_time time(0) without time zone,
    scheduled_date date,
    notes text,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT learning_sessions_status_check CHECK (((status)::text = ANY ((ARRAY['backlog'::character varying, 'planned'::character varying, 'scheduled'::character varying, 'done'::character varying])::text[])))
);


--
-- Name: learning_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.learning_sessions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: learning_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.learning_sessions_id_seq OWNED BY public.learning_sessions.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: reviews; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reviews (
    id bigint NOT NULL,
    session_id bigint,
    flashcard_id bigint,
    child_id bigint NOT NULL,
    topic_id bigint NOT NULL,
    interval_days integer DEFAULT 1 NOT NULL,
    ease_factor numeric(3,2) DEFAULT 2.5 NOT NULL,
    repetitions integer DEFAULT 0 NOT NULL,
    status character varying(255) DEFAULT 'new'::character varying NOT NULL,
    due_date date,
    last_reviewed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT reviews_status_check CHECK (((status)::text = ANY ((ARRAY['new'::character varying, 'learning'::character varying, 'reviewing'::character varying, 'mastered'::character varying])::text[])))
);


--
-- Name: reviews_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reviews_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reviews_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reviews_id_seq OWNED BY public.reviews.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: subjects; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subjects (
    id bigint NOT NULL,
    user_id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    color character varying(7) DEFAULT '#3b82f6'::character varying NOT NULL,
    child_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: subjects_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subjects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: subjects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.subjects_id_seq OWNED BY public.subjects.id;


--
-- Name: tasks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tasks (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    user_id bigint NOT NULL,
    due_date timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT tasks_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'medium'::character varying, 'high'::character varying, 'urgent'::character varying])::text[]))),
    CONSTRAINT tasks_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'in_progress'::character varying, 'completed'::character varying])::text[])))
);


--
-- Name: tasks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tasks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tasks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tasks_id_seq OWNED BY public.tasks.id;


--
-- Name: time_blocks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.time_blocks (
    id bigint NOT NULL,
    child_id bigint NOT NULL,
    day_of_week integer NOT NULL,
    start_time time(0) without time zone NOT NULL,
    end_time time(0) without time zone NOT NULL,
    label character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: time_blocks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.time_blocks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: time_blocks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.time_blocks_id_seq OWNED BY public.time_blocks.id;


--
-- Name: topics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.topics (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    estimated_minutes integer DEFAULT 30 NOT NULL,
    prerequisites json,
    required boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: topics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.topics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: topics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.topics_id_seq OWNED BY public.topics.id;


--
-- Name: units; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.units (
    id bigint NOT NULL,
    subject_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    target_completion_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: units_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.units_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: units_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.units_id_seq OWNED BY public.units.id;


--
-- Name: user_preferences; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_preferences (
    id bigint NOT NULL,
    locale character varying(5) DEFAULT 'en'::character varying NOT NULL,
    timezone character varying(50) DEFAULT 'UTC'::character varying NOT NULL,
    date_format character varying(20) DEFAULT 'Y-m-d'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    kids_mode_pin character varying(255),
    kids_mode_pin_salt character varying(255),
    kids_mode_pin_attempts integer DEFAULT 0 NOT NULL,
    kids_mode_pin_locked_until timestamp(0) without time zone,
    user_id bigint NOT NULL,
    onboarding_completed boolean DEFAULT false NOT NULL,
    onboarding_skipped boolean DEFAULT false NOT NULL
);


--
-- Name: COLUMN user_preferences.kids_mode_pin; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.user_preferences.kids_mode_pin IS 'Bcrypt hash of kids mode PIN';


--
-- Name: COLUMN user_preferences.kids_mode_pin_salt; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.user_preferences.kids_mode_pin_salt IS 'Additional security salt for PIN';


--
-- Name: COLUMN user_preferences.kids_mode_pin_attempts; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.user_preferences.kids_mode_pin_attempts IS 'Failed PIN attempts counter';


--
-- Name: COLUMN user_preferences.kids_mode_pin_locked_until; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.user_preferences.kids_mode_pin_locked_until IS 'Lockout timestamp after failed attempts';


--
-- Name: user_preferences_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_preferences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_preferences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_preferences_id_seq OWNED BY public.user_preferences.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    locale character varying(5) DEFAULT 'en'::character varying NOT NULL,
    timezone character varying(50) DEFAULT 'UTC'::character varying NOT NULL,
    date_format character varying(20) DEFAULT 'Y-m-d'::character varying NOT NULL
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: children id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.children ALTER COLUMN id SET DEFAULT nextval('public.children_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: flashcard_imports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flashcard_imports ALTER COLUMN id SET DEFAULT nextval('public.flashcard_imports_id_seq'::regclass);


--
-- Name: flashcards id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flashcards ALTER COLUMN id SET DEFAULT nextval('public.flashcards_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: kids_mode_audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kids_mode_audit_logs ALTER COLUMN id SET DEFAULT nextval('public.kids_mode_audit_logs_id_seq'::regclass);


--
-- Name: learning_sessions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.learning_sessions ALTER COLUMN id SET DEFAULT nextval('public.learning_sessions_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: reviews id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews ALTER COLUMN id SET DEFAULT nextval('public.reviews_id_seq'::regclass);


--
-- Name: subjects id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subjects ALTER COLUMN id SET DEFAULT nextval('public.subjects_id_seq'::regclass);


--
-- Name: tasks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks ALTER COLUMN id SET DEFAULT nextval('public.tasks_id_seq'::regclass);


--
-- Name: time_blocks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_blocks ALTER COLUMN id SET DEFAULT nextval('public.time_blocks_id_seq'::regclass);


--
-- Name: topics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.topics ALTER COLUMN id SET DEFAULT nextval('public.topics_id_seq'::regclass);


--
-- Name: units id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.units ALTER COLUMN id SET DEFAULT nextval('public.units_id_seq'::regclass);


--
-- Name: user_preferences id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_preferences ALTER COLUMN id SET DEFAULT nextval('public.user_preferences_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: children children_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.children
    ADD CONSTRAINT children_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: flashcard_imports flashcard_imports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flashcard_imports
    ADD CONSTRAINT flashcard_imports_pkey PRIMARY KEY (id);


--
-- Name: flashcards flashcards_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flashcards
    ADD CONSTRAINT flashcards_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: kids_mode_audit_logs kids_mode_audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kids_mode_audit_logs
    ADD CONSTRAINT kids_mode_audit_logs_pkey PRIMARY KEY (id);


--
-- Name: learning_sessions learning_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.learning_sessions
    ADD CONSTRAINT learning_sessions_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: reviews reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: subjects subjects_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subjects
    ADD CONSTRAINT subjects_pkey PRIMARY KEY (id);


--
-- Name: tasks tasks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_pkey PRIMARY KEY (id);


--
-- Name: time_blocks time_blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_blocks
    ADD CONSTRAINT time_blocks_pkey PRIMARY KEY (id);


--
-- Name: topics topics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.topics
    ADD CONSTRAINT topics_pkey PRIMARY KEY (id);


--
-- Name: units units_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.units
    ADD CONSTRAINT units_pkey PRIMARY KEY (id);


--
-- Name: user_preferences user_preferences_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_preferences
    ADD CONSTRAINT user_preferences_pkey PRIMARY KEY (id);


--
-- Name: user_preferences user_preferences_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_preferences
    ADD CONSTRAINT user_preferences_user_id_unique UNIQUE (user_id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: children_user_id_age_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX children_user_id_age_index ON public.children USING btree (user_id, age);


--
-- Name: children_user_id_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX children_user_id_name_index ON public.children USING btree (user_id, name);


--
-- Name: flashcard_imports_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcard_imports_status_index ON public.flashcard_imports USING btree (status);


--
-- Name: flashcard_imports_unit_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcard_imports_unit_id_status_index ON public.flashcard_imports USING btree (unit_id, status);


--
-- Name: flashcard_imports_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcard_imports_user_id_created_at_index ON public.flashcard_imports USING btree (user_id, created_at);


--
-- Name: flashcards_active_created_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_active_created_idx ON public.flashcards USING btree (is_active, created_at);


--
-- Name: flashcards_answer_fulltext_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_answer_fulltext_idx ON public.flashcards USING gin (to_tsvector('english'::regconfig, answer));


--
-- Name: flashcards_card_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_card_type_index ON public.flashcards USING btree (card_type);


--
-- Name: flashcards_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_deleted_at_index ON public.flashcards USING btree (deleted_at);


--
-- Name: flashcards_difficulty_level_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_difficulty_level_index ON public.flashcards USING btree (difficulty_level);


--
-- Name: flashcards_hint_fulltext_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_hint_fulltext_idx ON public.flashcards USING gin (to_tsvector('english'::regconfig, hint));


--
-- Name: flashcards_import_source_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_import_source_idx ON public.flashcards USING btree (import_source);


--
-- Name: flashcards_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_is_active_index ON public.flashcards USING btree (is_active);


--
-- Name: flashcards_question_fulltext_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_question_fulltext_idx ON public.flashcards USING gin (to_tsvector('english'::regconfig, question));


--
-- Name: flashcards_unit_active_created_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_unit_active_created_idx ON public.flashcards USING btree (unit_id, is_active, created_at);


--
-- Name: flashcards_unit_difficulty_active_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_unit_difficulty_active_idx ON public.flashcards USING btree (unit_id, difficulty_level, is_active);


--
-- Name: flashcards_unit_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_unit_id_index ON public.flashcards USING btree (unit_id);


--
-- Name: flashcards_unit_type_active_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flashcards_unit_type_active_idx ON public.flashcards USING btree (unit_id, card_type, is_active);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: kids_mode_audit_logs_action_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kids_mode_audit_logs_action_created_at_index ON public.kids_mode_audit_logs USING btree (action, created_at);


--
-- Name: kids_mode_audit_logs_ip_address_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kids_mode_audit_logs_ip_address_index ON public.kids_mode_audit_logs USING btree (ip_address);


--
-- Name: kids_mode_audit_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kids_mode_audit_logs_user_id_created_at_index ON public.kids_mode_audit_logs USING btree (user_id, created_at);


--
-- Name: learning_sessions_child_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX learning_sessions_child_id_index ON public.learning_sessions USING btree (child_id);


--
-- Name: learning_sessions_scheduled_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX learning_sessions_scheduled_date_index ON public.learning_sessions USING btree (scheduled_date);


--
-- Name: learning_sessions_scheduled_day_of_week_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX learning_sessions_scheduled_day_of_week_index ON public.learning_sessions USING btree (scheduled_day_of_week);


--
-- Name: learning_sessions_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX learning_sessions_status_index ON public.learning_sessions USING btree (status);


--
-- Name: learning_sessions_topic_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX learning_sessions_topic_id_index ON public.learning_sessions USING btree (topic_id);


--
-- Name: reviews_child_id_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_child_id_due_date_index ON public.reviews USING btree (child_id, due_date);


--
-- Name: reviews_child_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_child_id_status_index ON public.reviews USING btree (child_id, status);


--
-- Name: reviews_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_due_date_index ON public.reviews USING btree (due_date);


--
-- Name: reviews_flashcard_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_flashcard_id_index ON public.reviews USING btree (flashcard_id);


--
-- Name: reviews_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_session_id_index ON public.reviews USING btree (session_id);


--
-- Name: reviews_topic_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_topic_id_index ON public.reviews USING btree (topic_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: subjects_child_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subjects_child_id_index ON public.subjects USING btree (child_id);


--
-- Name: subjects_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subjects_user_id_index ON public.subjects USING btree (user_id);


--
-- Name: tasks_user_id_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tasks_user_id_due_date_index ON public.tasks USING btree (user_id, due_date);


--
-- Name: tasks_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tasks_user_id_status_index ON public.tasks USING btree (user_id, status);


--
-- Name: time_blocks_child_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_blocks_child_id_index ON public.time_blocks USING btree (child_id);


--
-- Name: time_blocks_day_of_week_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_blocks_day_of_week_index ON public.time_blocks USING btree (day_of_week);


--
-- Name: topics_required_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX topics_required_index ON public.topics USING btree (required);


--
-- Name: topics_unit_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX topics_unit_id_index ON public.topics USING btree (unit_id);


--
-- Name: units_subject_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX units_subject_id_index ON public.units USING btree (subject_id);


--
-- Name: children children_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.children
    ADD CONSTRAINT children_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: flashcard_imports flashcard_imports_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flashcard_imports
    ADD CONSTRAINT flashcard_imports_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE CASCADE;


--
-- Name: flashcard_imports flashcard_imports_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flashcard_imports
    ADD CONSTRAINT flashcard_imports_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: flashcards flashcards_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flashcards
    ADD CONSTRAINT flashcards_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE CASCADE;


--
-- Name: learning_sessions learning_sessions_child_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.learning_sessions
    ADD CONSTRAINT learning_sessions_child_id_foreign FOREIGN KEY (child_id) REFERENCES public.children(id) ON DELETE CASCADE;


--
-- Name: learning_sessions learning_sessions_topic_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.learning_sessions
    ADD CONSTRAINT learning_sessions_topic_id_foreign FOREIGN KEY (topic_id) REFERENCES public.topics(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_child_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_child_id_foreign FOREIGN KEY (child_id) REFERENCES public.children(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_flashcard_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_flashcard_id_foreign FOREIGN KEY (flashcard_id) REFERENCES public.flashcards(id) ON DELETE SET NULL;


--
-- Name: reviews reviews_session_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_session_id_foreign FOREIGN KEY (session_id) REFERENCES public.learning_sessions(id) ON DELETE SET NULL;


--
-- Name: reviews reviews_topic_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_topic_id_foreign FOREIGN KEY (topic_id) REFERENCES public.topics(id) ON DELETE CASCADE;


--
-- Name: subjects subjects_child_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subjects
    ADD CONSTRAINT subjects_child_id_foreign FOREIGN KEY (child_id) REFERENCES public.children(id) ON DELETE CASCADE;


--
-- Name: tasks tasks_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: time_blocks time_blocks_child_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_blocks
    ADD CONSTRAINT time_blocks_child_id_foreign FOREIGN KEY (child_id) REFERENCES public.children(id) ON DELETE CASCADE;


--
-- Name: topics topics_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.topics
    ADD CONSTRAINT topics_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE CASCADE;


--
-- Name: units units_subject_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.units
    ADD CONSTRAINT units_subject_id_foreign FOREIGN KEY (subject_id) REFERENCES public.subjects(id) ON DELETE CASCADE;


--
-- Name: user_preferences user_preferences_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_preferences
    ADD CONSTRAINT user_preferences_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

--
-- PostgreSQL database dump
--

-- Dumped from database version 17.5
-- Dumped by pg_dump version 17.5

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_09_05_072734_add_locale_fields_to_users_table	1
5	2025_09_06_095806_create_user_preferences_table	1
6	2025_09_06_095807_add_kids_mode_pin_to_user_preferences	1
7	2025_09_06_112629_create_kids_mode_audit_logs_table	1
8	2025_09_08_101504_create_children_table	1
9	2025_09_08_102952_create_subjects_table	1
10	2025_09_08_103036_create_units_table	1
11	2025_09_08_103037_create_time_blocks_table	1
12	2025_09_08_103038_create_topics_table	1
13	2025_09_08_103039_create_sessions_table	1
14	2025_09_08_103651_fix_subjects_user_id_type	1
15	2025_09_08_122448_create_tasks_table	1
16	2025_09_08_130131_fix_user_preferences_user_id_and_add_onboarding_completed	1
17	2025_09_09_172922_create_flashcards_table	1
18	2025_09_09_173818_create_reviews_table	1
19	2025_09_09_224635_create_flashcard_imports_table	1
20	2025_09_09_230945_add_performance_indexes_to_flashcards_table	1
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 20, true);


--
-- PostgreSQL database dump complete
--

