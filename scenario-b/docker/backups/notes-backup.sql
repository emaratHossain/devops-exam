--
-- PostgreSQL database dump
--

\restrict sdxq1VuHPENURQBkhaVlgeWW4MU8A3LWB69HdMsNM7E6zlBMxYNWTt8S3d1nv7r

-- Dumped from database version 16.15 (Debian 16.15-1.pgdg13+2)
-- Dumped by pg_dump version 16.15 (Debian 16.15-1.pgdg13+2)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: migrations; Type: TABLE; Schema: public; Owner: notes
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO notes;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: notes
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO notes;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notes
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notes; Type: TABLE; Schema: public; Owner: notes
--

CREATE TABLE public.notes (
    id bigint NOT NULL,
    tenant_id bigint NOT NULL,
    title text NOT NULL,
    body text NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.notes OWNER TO notes;

--
-- Name: notes_id_seq; Type: SEQUENCE; Schema: public; Owner: notes
--

CREATE SEQUENCE public.notes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notes_id_seq OWNER TO notes;

--
-- Name: notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notes
--

ALTER SEQUENCE public.notes_id_seq OWNED BY public.notes.id;


--
-- Name: tags; Type: TABLE; Schema: public; Owner: notes
--

CREATE TABLE public.tags (
    id bigint NOT NULL,
    note_id bigint NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.tags OWNER TO notes;

--
-- Name: tags_id_seq; Type: SEQUENCE; Schema: public; Owner: notes
--

CREATE SEQUENCE public.tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tags_id_seq OWNER TO notes;

--
-- Name: tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notes
--

ALTER SEQUENCE public.tags_id_seq OWNED BY public.tags.id;


--
-- Name: tenants; Type: TABLE; Schema: public; Owner: notes
--

CREATE TABLE public.tenants (
    id bigint NOT NULL,
    slug character varying(255) NOT NULL
);


ALTER TABLE public.tenants OWNER TO notes;

--
-- Name: tenants_id_seq; Type: SEQUENCE; Schema: public; Owner: notes
--

CREATE SEQUENCE public.tenants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tenants_id_seq OWNER TO notes;

--
-- Name: tenants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: notes
--

ALTER SEQUENCE public.tenants_id_seq OWNED BY public.tenants.id;


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: notes id; Type: DEFAULT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.notes ALTER COLUMN id SET DEFAULT nextval('public.notes_id_seq'::regclass);


--
-- Name: tags id; Type: DEFAULT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.tags ALTER COLUMN id SET DEFAULT nextval('public.tags_id_seq'::regclass);


--
-- Name: tenants id; Type: DEFAULT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.tenants ALTER COLUMN id SET DEFAULT nextval('public.tenants_id_seq'::regclass);


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: notes
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2026_01_01_000001_create_tenants_table	1
2	2026_01_01_000002_create_notes_table	1
3	2026_01_01_000003_create_tags_table	1
\.


--
-- Data for Name: notes; Type: TABLE DATA; Schema: public; Owner: notes
--

COPY public.notes (id, tenant_id, title, body, created_at) FROM stdin;
\.


--
-- Data for Name: tags; Type: TABLE DATA; Schema: public; Owner: notes
--

COPY public.tags (id, note_id, name) FROM stdin;
\.


--
-- Data for Name: tenants; Type: TABLE DATA; Schema: public; Owner: notes
--

COPY public.tenants (id, slug) FROM stdin;
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notes
--

SELECT pg_catalog.setval('public.migrations_id_seq', 3, true);


--
-- Name: notes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notes
--

SELECT pg_catalog.setval('public.notes_id_seq', 1, false);


--
-- Name: tags_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notes
--

SELECT pg_catalog.setval('public.tags_id_seq', 1, false);


--
-- Name: tenants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: notes
--

SELECT pg_catalog.setval('public.tenants_id_seq', 1, false);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notes notes_pkey; Type: CONSTRAINT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.notes
    ADD CONSTRAINT notes_pkey PRIMARY KEY (id);


--
-- Name: tags tags_pkey; Type: CONSTRAINT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (id);


--
-- Name: tenants tenants_pkey; Type: CONSTRAINT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_pkey PRIMARY KEY (id);


--
-- Name: tenants tenants_slug_unique; Type: CONSTRAINT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_slug_unique UNIQUE (slug);


--
-- Name: notes_tenant_id_id_index; Type: INDEX; Schema: public; Owner: notes
--

CREATE INDEX notes_tenant_id_id_index ON public.notes USING btree (tenant_id, id);


--
-- Name: notes notes_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.notes
    ADD CONSTRAINT notes_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id);


--
-- Name: tags tags_note_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: notes
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_note_id_foreign FOREIGN KEY (note_id) REFERENCES public.notes(id);


--
-- PostgreSQL database dump complete
--

\unrestrict sdxq1VuHPENURQBkhaVlgeWW4MU8A3LWB69HdMsNM7E6zlBMxYNWTt8S3d1nv7r

