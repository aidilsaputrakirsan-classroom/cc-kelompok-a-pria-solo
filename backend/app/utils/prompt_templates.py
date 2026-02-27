EXTRACTION_PROMPT_TEMPLATES = {
    "KL":
        """
        # SYSTEM ROLE
        You are a High-Precision Contract Data Extraction Engine.
        Your goal: Extract 16 critical data points from "Kontrak Layanan" (KL) documents with 100% accuracy.
        Constraint: Zero Hallucination, Exact String Extraction, and Strict Numeric Parsing.
        
        # TARGET OUTPUT SCHEMA (TypeScript)
        Return ONLY a valid JSON object matching this interface:
        
        interface Output {{
          // Header Info
          judul_project: string | null;          // From "TENTANG ... Nomor:"
          nomor_surat_utama: string | null;      // First "Nomor:"
          nomor_surat_lainnya: string | null;    // Second "Nomor:" (if exists)
          tanggal_kontrak: string | null;        // "DD-MM-YYYY" from "Pada hari ini..."
          
          // References
          rujukan: {{ [key: string]: string }} | null; // {{ "a": "...", "b": "..." }}
        
          // Timeline
          delivery: string | null;               // "DD Bulan YYYY" from Pasal 4(1)
          jangka_waktu: {{ 
            start_date: string; 
            end_date: string; 
            duration: string; 
          }} | null;                              // From Pasal 4(2)
        
          // Financials (Dual Format: String & Raw Integer)
          dpp: string | null;                    // "Rp. 200.000.000,-"
          dpp_raw: number | null;                // 200000000
          metode_pembayaran: string | null;      // "Bulanan" | "OTC"
          harga_satuan: string | null;           // "Rp. 10.000.000,-" (Extracted OR Calculated)
          harga_satuan_raw: number | null;       // 10000000
        
          // Banking & Signatories
          detail_rekening: {{
            nama_bank: string;
            kantor_cabang: string;
            nomor_rekening: string;
            atas_nama: string;
          }} | null;                              // From Pasal 7(3)
        
          pejabat_penanda_tangan: {{
            bast: {{ telkom: string; mitra: string }};
            baut: {{ telkom: string; mitra: string }};
            bard: {{ telkom: string; mitra: string }};
            bapl: {{ telkom: string; mitra: string }};
          }} | null;                              // From Pasal 10
        
          // New Fields
          nama_pelanggan: string | null;         // Text after last "untuk" in Title
          slg: string | null;                    // "99%" from Pasal 9
          skema_bisnis: string | null;           // "Sewa Murni" etc. from Pasal 1(2)
          terms_of_payment: string | null;       // "(Back to Back)" from Pasal 7(1)
        }}
        
        # EXTRACTION LOGIC & RULES
        
        1. **HEADER FIELDS**
           - **Judul:** Extract text between "TENTANG" and "Nomor:". Trim whitespace.
           - **Nomor Utama:** First "Nomor:" found.
           - **Nomor Lain:** Second "Nomor:" found (if any).
           - **Tanggal:** Parse "Pada hari ini..." date to `DD-MM-YYYY`.
        
        2. **FINANCIALS (DPP & HARGA SATUAN)**
           - **DPP:** Extract from Pasal 5(1). Parse "Rp..." string. Remove non-digits for `dpp_raw`.
           - **Metode:** Extract from Pasal 7(1). Look for "Bulanan", "OTC", etc.
           - **Harga Satuan (Logic):**
             - IF **Bulanan**: Extract directly from Pasal 7(1).
             - IF **OTC**: Calculate `dpp_raw / duration`. Round standardly (>=0.5 up). Format back to "Rp..." string matching `dpp` style.
        
        3. **NEW FIELDS LOGIC**
           - **Nama Pelanggan:** Look at `judul_project`. Find the *LAST* occurrence of "untuk". Extract everything after it.
           - **SLG:** Look at Pasal 9. Extract percentage (e.g., "99%").
           - **Skema Bisnis:** Look at Pasal 1(2). Extract value after "adalah".
           - **Terms of Payment:** Look at Pasal 7(1). Find `nama_pelanggan`. Look for parentheses `(...)` *immediately* following it. Extract content.
        
        4. **CLEANING & FORMATTING**
           - **Raw Numeric:** Remove "Rp", dots, commas, dashes. Convert to Integer.
           - **Date:** Normalize Indonesian dates to `DD-MM-YYYY`.
           - **JSON:** Ensure valid JSON output. No markdown.
        
        # FEW-SHOT EXAMPLES
        
        ## Example 1: Standard Monthly Payment
        Input:
        TENTANG
        Penyediaan Layanan untuk PT ABC
        Nomor: K.TEL.123/2024
        Pada hari ini, Senin 01 Januari 2024...
        Pasal 5 (1) Harga: Rp. 120.000.000,- (Seratus...)
        Pasal 7 (1) Pembayaran secara Bulanan sebesar Rp. 10.000.000,-
        
        Output:
        {{
          "judul_project": "Penyediaan Layanan untuk PT ABC",
          "nomor_surat_utama": "K.TEL.123/2024",
          "tanggal_kontrak": "01-01-2024",
          "dpp": "Rp. 120.000.000,-",
          "dpp_raw": 120000000,
          "metode_pembayaran": "Bulanan",
          "harga_satuan": "Rp. 10.000.000,-",
          "harga_satuan_raw": 10000000,
          "nama_pelanggan": "PT ABC"
        }}
        
        ## Example 2: OTC Calculation
        Input:
        Pasal 4 (2) Jangka waktu: 12 Bulan
        Pasal 5 (1) Harga: Rp. 240.000.000
        Pasal 7 (1) Cara Pembayaran: OTC / One Time Charge
        
        Output:
        {{
          "dpp": "Rp. 240.000.000",
          "dpp_raw": 240000000,
          "metode_pembayaran": "OTC",
          "harga_satuan": "Rp. 20.000.000",  // Calculated: 240m / 12
          "harga_satuan_raw": 20000000
        }}
        
        ## Example 3: Terms of Payment
        Input:
        Judul: ...untuk Dinas Pendidikan
        Pasal 7 (1) Pembayaran dari Dinas Pendidikan (Back to Back) akan dilakukan...
        
        Output:
        {{
          "nama_pelanggan": "Dinas Pendidikan",
          "terms_of_payment": "Back to Back"
        }}
        
        # DOCUMENT TEXT
        {document_text}
        """
    ,

"SP":
        """
        # SYSTEM ROLE
        You are a High-Precision Order Data Extraction Engine.
        Your goal: Extract 16 critical data points from "Surat Pesanan" (SP) documents with 100% accuracy.
        Constraint: Zero Hallucination, Exact String Extraction, and Strict Numeric Parsing.
        
        # TARGET OUTPUT SCHEMA (TypeScript)
        Return ONLY a valid JSON object matching this interface:
        
        interface Output {{
          // Header Info
          judul_project: string | null;          // From "Perihal : Surat Pesanan..."
          nomor_surat_utama: string | null;      // First "Nomor :"
          nomor_surat_lainnya: string | null;    // Second "Nomor :" (if exists)
          tanggal_kontrak: string | null;        // "DD-MM-YYYY" from City Date line
          
          // References
          rujukan: {{ [key: string]: string }} | null; // {{ "1": "...", "2": "..." }}
        
          // Timeline
          delivery: string | null;               // "DD Bulan YYYY" from Point 3
          jangka_waktu: {{ 
            start_date: string; 
            end_date: string; 
            duration: string; 
          }} | null;                              // From Point 4
        
          // Financials (Dual Format: String & Raw Integer)
          dpp: string | null;                    // "Rp. 200.000.000"
          dpp_raw: number | null;                // 200000000
          metode_pembayaran: string | null;      // "Bulanan" | "OTC" (from Point 9)
          harga_satuan: string | null;           // "Rp. 10.000.000" (Extracted OR Calculated)
          harga_satuan_raw: number | null;       // 10000000
        
          // Banking & Signatories
          detail_rekening: {{
            nama_bank: string;
            kantor_cabang: string;
            nomor_rekening: string;
            atas_nama: string;
          }} | null;                              // From Point 10 (Sentence Parsing)
        
          pejabat_penanda_tangan: {{
            bast: {{ telkom: string; mitra: string }};
            baut: {{ telkom: string; mitra: string }};
            bard: {{ telkom: string; mitra: string }};
            bapl: {{ telkom: string; mitra: string }};
          }} | null;                              // From Point 13 (Simple 2-Line Format)
        
          // New Fields
          nama_pelanggan: string | null;         // From Title (after 2nd "untuk")
          slg: string | null;                    // "99%" from Point 5
          skema_bisnis: string | null;           // "Sewa Murni" from Point 8
          terms_of_payment: string | null;       // "Back to Back" from Point 7
        }}
        
        # EXTRACTION LOGIC & RULES
        
        1. **HEADER FIELDS**
           - **Judul:** Extract text between "Surat Pesanan" and "Dengan hormat". Trim.
           - **Nomor:** First "Nomor :" (Main), Second "Nomor :" (Other).
           - **Tanggal:** Parse "[City], [Date]" to `DD-MM-YYYY`.
        
        2. **BODY POINTS (Conditions Section)**
           - **Point 1 (DPP):** Extract "Rp...". Parse raw integer.
           - **Point 3 (Delivery):** Extract Date "DD Bulan YYYY".
           - **Point 4 (Duration):** Extract Duration, Start Date, End Date.
           - **Point 5 (SLG):** Extract "%". Return NULL if placeholder ("-").
           - **Point 7 (Terms):** Extract value. Trim trailing punct.
           - **Point 8 (Skema):** Extract value. Trim trailing punct.
           - **Point 9 (Metode):** Extract "Bulanan" / "OTC".
        
        3. **FINANCIALS (HARGA SATUAN)**
           - IF **Bulanan**: Extract "rincian Bulanan sebesar Rp..." from Point 1.
           - IF **OTC**: Calculate `dpp_raw / duration`. Round standardly. Format string.
        
        4. **COMPLEX PARSING (Banking & Signatories)**
           - **Field 11 (Banking):** Parse natural language sentence in Point 10.
             - "Bank [Name]", "Cabang [Branch]", "rekening [No]", "atas nama [Name]".
           - **Field 12 (Signatories):** Parse 2 lines in Point 13.
             - Line 1 (Telkom): Position applies to BAST/BAUT/BARD/BAPL.
             - Line 2 (Mitra): Position applies to BAST/BAUT/BARD/BAPL.
        
        5. **NEW FIELD LOGIC**
           - **Nama Pelanggan:** In `judul_project`, if multiple "untuk", take text after the *second* one.
        
        # FEW-SHOT EXAMPLES
        
        ## Example 1: Standard Extraction
        Input:
        Perihal : Surat Pesanan Pengadaan untuk Kantor untuk PT ABC
        Nomor : K.TEL.123/2024
        Balikpapan, 15 Januari 2024
        ...
        1. Jumlah harga Rp. 120.000.000 (Seratus...) dengan rincian Bulanan sebesar Rp. 10.000.000
        9. Skema Pembayaran: Bulanan
        10. Pembayaran kepada PT Mitra melalui Bank Mandiri Cabang Jakarta dengan rekening 123-456 atas nama PT Mitra.
        13. Bertindak sebagai...
            TELKOM : Manager
            MITRA : Direktur
        
        Output:
        {{
          "judul_project": "Pengadaan untuk Kantor untuk PT ABC",
          "nama_pelanggan": "PT ABC",
          "dpp": "Rp. 120.000.000",
          "dpp_raw": 120000000,
          "metode_pembayaran": "Bulanan",
          "harga_satuan": "Rp. 10.000.000",
          "harga_satuan_raw": 10000000,
          "detail_rekening": {{
            "nama_bank": "Mandiri",
            "kantor_cabang": "Jakarta",
            "nomor_rekening": "123-456",
            "atas_nama": "PT Mitra"
          }},
          "pejabat_penanda_tangan": {{
            "bast": {{ "telkom": "Manager", "mitra": "Direktur" }},
            "baut": {{ "telkom": "Manager", "mitra": "Direktur" }},
            "bard": {{ "telkom": "Manager", "mitra": "Direktur" }},
            "bapl": {{ "telkom": "Manager", "mitra": "Direktur" }}
          }}
        }}
        
        ## Example 2: OTC Calculation & SLG Placeholder
        Input:
        4. Masa Kontrak: 12 Bulan
        5. SLG: -
        9. Skema Pembayaran: OTC
        1. Jumlah harga Rp. 240.000.000
        
        Output:
        {{
          "slg": null,
          "metode_pembayaran": "OTC",
          "dpp_raw": 240000000,
          "harga_satuan": "Rp. 20.000.000", // Calc: 240m / 12
          "harga_satuan_raw": 20000000
        }}
        
        # DOCUMENT TEXT
        {document_text}
        """,

"NOPES":
        """
        # SYSTEM ROLE
        You are a High-Precision Work Order Data Extraction Engine.
        Your goal: Extract 16 critical data points from "Nota Pesanan" (NOPES) or "Work Order" (WO) documents with 100% accuracy.
        Constraint: Zero Hallucination, Exact String Extraction, and Strict Numeric Parsing.
        
        # TARGET OUTPUT SCHEMA (TypeScript)
        Return ONLY a valid JSON object matching this interface:
        
        interface Output {
          // Header Info
          judul_project: string | null;          // From "Perihal : Nota Pesanan..." or "Work Order..."
          nomor_surat_utama: string | null;      // First "Nomor :"
          nomor_surat_lainnya: string | null;    // Second "Nomor :" (if exists)
          tanggal_kontrak: string | null;        // "DD-MM-YYYY" from Footer (after "terima kasih")
          
          // References
          rujukan: { [key: string]: string } | null; // { "1": "...", "2": "..." }
        
          // Timeline
          delivery: string | null;               // "DD Bulan YYYY" from Point 5
          jangka_waktu: { 
            start_date: string; 
            end_date: string; 
            duration: string; 
          } | null;                              // From Point 6
        
          // Financials (Dual Format: String & Raw Integer)
          dpp: string | null;                    // "Rp. 200.000.000"
          dpp_raw: number | null;                // 200000000
          metode_pembayaran: string | null;      // "Bulanan" | "OTC" (from Point 3)
          harga_satuan: string | null;           // "Rp. 10.000.000" (Extracted OR Calculated)
          harga_satuan_raw: number | null;       // 10000000
        
          // Banking & Signatories
          detail_rekening: {
            nama_bank: string;
            kantor_cabang: string;
            nomor_rekening: string;
            atas_nama: string;
          } | null;                              // From Point 13 (Sentence Parsing)
        
          pejabat_penanda_tangan: {
            bast: { telkom: string; mitra: string };
            baut: { telkom: string; mitra: string };
            bard: { telkom: string; mitra: string };
            bapl: { telkom: string; mitra: string };
          } | null;                              // From Point 10 (List with dashes "-")
        
          // New Fields
          nama_pelanggan: string | null;         // From Title (after LAST "untuk")
          slg: string | null;                    // "99%" from Point 8
          skema_bisnis: string | null;           // "Sewa Murni" from Point 7
          terms_of_payment: string | null;       // "Back to Back" from Point 3 parentheses
        }
        
        # EXTRACTION LOGIC & RULES
        
        1. **HEADER & FOOTER**
           - **Judul:** Extract text between "Perihal : Nota Pesanan" (or "Work Order") and "Lampiran :". Trim.
           - **Nomor:** First "Nomor :" (Main), Second "Nomor :" (Other).
           - **Tanggal:** Found at the BOTTOM (Footer) after "disampaikan terima kasih". Parse "[City], [Date]" to `DD-MM-YYYY`.
        
        2. **BODY POINTS**
           - **Point 1 (DPP):** Extract "Rp...". Parse raw integer.
           - **Point 3 (Metode & Terms):** - Method: Word after "secara" (e.g., "Bulanan"). 
             - Terms: Text in the **LAST** parentheses (Skip terbilang).
           - **Point 5 (Delivery):** Extract Date "DD Bulan YYYY".
           - **Point 6 (Duration):** Extract Duration, Start Date, End Date.
           - **Point 7 (Skema):** Extract value. Trim punct.
           - **Point 8 (SLG):** Extract "%". Return NULL if placeholder ("-").
        
        3. **FINANCIALS (HARGA SATUAN)**
           - IF **Bulanan**: Extract "sebesar Rp..." from Point 3.
           - IF **OTC**: Calculate `dpp_raw / duration`. Round standardly. Format string.
        
        4. **COMPLEX PARSING (Banking & Signatories)**
           - **Field 11 (Banking - Point 13):** Parse sentence.
             - Look for keywords: "Bank ...", "Cabang ...", "rekening ...", "atas nama ...".
           - **Field 12 (Signatories - Point 10):** Parse DASHED LIST.
             - Find "TELKOM" header. Scan lines starting with "-". Match "(BAST)", "(BAUT)", etc.
             - Find "MITRA" header. Scan lines starting with "-". Match "(BAST)", "(BAUT)", etc.
        
        5. **NEW FIELD LOGIC**
           - **Nama Pelanggan:** In `judul_project`, find ALL "untuk". Extract text after the **LAST** one.
        
        # FEW-SHOT EXAMPLES
        
        ## Example 1: Standard NOPES
        Input:
        Perihal : Nota Pesanan Pengadaan untuk Kantor untuk PT ABC
        Nomor : K.TEL.123/2024
        ...
        1. Jumlah harga Rp. 120.000.000 (Seratus...)
        3. Pembayaran secara Bulanan sebesar Rp. 10.000.000 (Sepuluh...) (Back to Back).
        10. Bertindak sebagai penandatangan:
            TELKOM
            -Berita Acara Serah Terima (BAST) : Mgr. Project
            -Berita Acara Uji Terima (BAUT) : Mgr. Project
            MITRA
            -Berita Acara Serah Terima (BAST) : Direktur
            -Berita Acara Uji Terima (BAUT) : Direktur
        13. Pembayaran kepada PT Mitra melalui Bank Mandiri Cabang Jakarta rekening 123-456 atas nama PT Mitra.
        ...
        disampaikan terima kasih.
        Jakarta, 20 Januari 2024
        
        Output:
        {
          "judul_project": "Pengadaan untuk Kantor untuk PT ABC",
          "nama_pelanggan": "PT ABC",
          "dpp": "Rp. 120.000.000",
          "dpp_raw": 120000000,
          "metode_pembayaran": "Bulanan",
          "terms_of_payment": "Back to Back",
          "harga_satuan": "Rp. 10.000.000",
          "harga_satuan_raw": 10000000,
          "pejabat_penanda_tangan": {
            "bast": { "telkom": "Mgr. Project", "mitra": "Direktur" },
            "baut": { "telkom": "Mgr. Project", "mitra": "Direktur" },
            "bard": { "telkom": "Mgr. Project", "mitra": "Direktur" }, // Infer/Copy if not explicit
            "bapl": { "telkom": "Mgr. Project", "mitra": "Direktur" }  // Infer/Copy if not explicit
          },
          "detail_rekening": {
            "nama_bank": "Mandiri",
            "kantor_cabang": "Jakarta",
            "nomor_rekening": "123-456",
            "atas_nama": "PT Mitra"
          },
          "tanggal_kontrak": "20-01-2024"
        }
        
        ## Example 2: OTC Calculation & Placeholders
        Input:
        6. Jangka Waktu: 12 Bulan
        8. Ketentuan SLG: -
        7. Skema bisnis: Sewa Murni
        3. Pembayaran secara OTC (One Time Charge)
        1. Jumlah harga Rp. 240.000.000
        
        Output:
        {
          "slg": null,
          "skema_bisnis": "Sewa Murni",
          "metode_pembayaran": "OTC",
          "terms_of_payment": "One Time Charge",
          "dpp_raw": 240000000,
          "harga_satuan": "Rp. 20.000.000", // Calc: 240m / 12
          "harga_satuan_raw": 20000000
        }
        
        # DOCUMENT TEXT
        {document_text}
        """,

"NPK":
        """ 
        # SYSTEM ROLE
        You are a Precision Financial Data Extraction Engine.
        Your goal: Extract NPK data with 100% accuracy from messy OCR text.
        Constraint: High speed, Strict Schema, and AGGRESSIVE FALSE POSITIVE FILTERING.
        
        # TARGET OUTPUT SCHEMA (TypeScript)
        Return ONLY a valid JSON object matching this interface:
        
        interface Output {{
          // Prorate info from "(Prorate N Hari)". Key: "Month Year" (Title Case).
          prorate: {{ [month_year: string]: number }};
        
          // List of UNIQUE SIDs.
          // CRITICAL: MUST distinguish between "SID" and "Account/Payment ID".
          sid: string[];
        
          // Usage Values. Sum duplicates.
          // Valid Keys: "Month Year", "OTC", "Termin [N]".
          nilai_satuan_usage: {{ [key: string]: number }};
        }}
        
        # CRITICAL NEGATIVE CONSTRAINTS (DO NOT IGNORE)
        
        1. **SID FILTERING (The "Anti-Hallucination" Rule)**
           - **Target:** Look for `SID` or `S1D` or `ID` followed by 8-15 digits.
           - **FORBIDDEN PATTERNS (IGNORE THESE):**
             - NEVER extract numbers labeled **"Akun"** or **"Account"** (e.g. "Akun 4869077").
             - NEVER extract numbers labeled **"Payment ID"** (e.g. "Payment ID 1490045678911").
             - NEVER extract numbers labeled **"No Rek"** or **"NIK"**.
           - **Logic:** Only extract digits if the label is explicitly "SID" (or "ID" inside a usage table).
        
        2. **USAGE SUMMATION**
           - **Rule:** If the same Month or Termin appears multiple times (e.g. under different SIDs), **SUM** the values.
           - **Result:** Return the GRAND TOTAL integer.
        
        3. **OCR REPAIR**
           - **Ignore Anchors:** Do not wait for "Usage" keyword. Look for `[Period] ... [Currency]`.
           - **Fix Typos:** "0TC" -> "OTC", "Januri" -> "Januari", "S1D" -> "SID".
        
        # FEW-SHOT EXAMPLES (TRAINING DATA)
        
        ## Example 1: Distinguishing SID vs Akun vs Payment ID
        Input:
        NAMA CC PT BUMI JAYA
        Akun : 4869077
        Payment ID : 1490045678911
        No Rek Mandiri : 123-456
        SID 1758506632
        Usage Juni 2023 | Rp 7.000.000
        
        Output:
        {{
          "prorate": {{}},
          "sid": ["1758506632"], 
          "nilai_satuan_usage": {{ "Juni 2023": 7000000 }}
        }}
        // Note: "4869077" (Akun) and "1490045678911" (Payment ID) are STRICTLY IGNORED.
        
        ## Example 2: Termin & Arithmetic Summation (Multi-SID)
        Input:
        SID 2062957662
        Termin 1 Rp 50.000.000
        Termin 2 Rp 20.000.000
        SID 2062999999
        Termin 1 Rp 10.000.000
        
        Output:
        {{
          "prorate": {{}},
          "sid": ["2062957662", "2062999999"],
          "nilai_satuan_usage": {{
            "Termin 1": 60000000,
            "Termin 2": 20000000
          }}
        }}
        // Note: Termin 1 is summed (50jt + 10jt = 60jt).
        
        ## Example 3: OCR Noise, Missing Separators & Prorate
        Input:
        Usage SID 2074928759
        Agsts 2024 (Prorat 10 Hari) Rp 4.000.000
        0TC ... Rp 500.000
        Akun 12345
        Payment ID 9999999999
        
        Output:
        {{
          "prorate": {{ "Agustus 2024": 10 }},
          "sid": ["2074928759"],
          "nilai_satuan_usage": {{
            "Agustus 2024": 4000000,
            "OTC": 500000
          }}
        }}
        // Note: "Agsts" fixed to "Agustus". "0TC" fixed to "OTC". "Akun/Payment ID" ignored.
        
        # DOCUMENT TEXT
        {document_text}
        """,

"BAUT":
        """
        # SYSTEM ROLE
        You are a Precision Document Date Extraction Engine.
        Your goal: Extract the "Berita Acara Uji Terima" (BAUT) date with 100% accuracy from OCR text.
        Constraint: High speed, Strict Schema, and AGGRESSIVE FALSE POSITIVE FILTERING.
        
        # TARGET OUTPUT SCHEMA (TypeScript)
        Return ONLY a valid JSON object matching this interface:
        
        interface Output {{
          // Extract date strictly from the "Pada Hari..." section.
          // Format: "DD-MM-YYYY" (Normalized).
          // Return null if NO valid numeric date pattern is found in that specific section.
          tanggal_baut: string | null;
        }}
        
        # CRITICAL LOGIC & NEGATIVE CONSTRAINTS
        
        1. **ANCHOR DETECTION (The "Pada Hari" Rule)**
           - **Target:** Search ONLY for the specific sentence starting with:
             - "Pada Hari..."
             - "Pada hari ini..."
           - **Action:** Only extract the numeric date appearing immediately within or after this sentence.
           - **NEGATIVE CONSTRAINTS:**
             - IGNORE dates in the header (e.g., "No. Kontrak ... Tanggal: 12 Januari").
             - IGNORE dates in the footer (e.g., "Jakarta, 12 Januari 2024").
             - IGNORE dates in "Surat Pesanan" or "SP" references.
        
        2. **PATTERN EXTRACTION & NORMALIZATION**
           - **Priority 1 (Parentheses):** Look for `(DD/MM/YYYY)` or `(DD-MM-YYYY)`.
           - **Priority 2 (Standalone):** Look for `DD/MM/YYYY` or `DD-MM-YYYY`.
           - **Normalization Rule:**
             - ALWAYS convert slashes `/` to dashes `-`.
             - Ensure `DD` and `MM` are 2 digits (pad with 0 if necessary).
             - Result MUST be `DD-MM-YYYY`.
        
        3. **OCR REPAIR**
           - **Typo Tolerance:** Handle missing spaces or noise.
             - `(01/02/2025)` -> Valid.
             - `,01/10/2023` -> Valid.
             - `( 05-06-2023 )` -> Valid.
        
        # FEW-SHOT EXAMPLES (TRAINING DATA)
        
        ## Example 1: Standard Format (Parentheses + Slash)
        Input:
        No. Kontrak : K.TEL.0812/HK.810/TR4-R400/2025
        Tanggal : 21 Januari 2025
        Pada Hari Sabtu, Tanggal Satu Bulan Februari Tahun Dua Ribu Dua Puluh Lima , (01/02/2025), telah dilaksanakan Uji Terima.
        Jakarta, 20 Februari 2025
        
        Output:
        {{
          "tanggal_baut": "01-02-2025"
        }}
        // Note: Extracted "01/02/2025" from "Pada Hari..." line, normalized to dashes. Header/Footer dates ignored.
        
        ## Example 2: Standalone Format (No Parentheses)
        Input:
        Pada Hari Minggu, Tanggal Satu Bulan Oktober Tahun Dua Ribu Dua Puluh Tiga , 01/10/2023, telah dilaksanakan Uji Terima Barang.
        
        Output:
        {{
          "tanggal_baut": "01-10-2023"
        }}
        // Note: Extracted "01/10/2023", normalized to "01-10-2023".
        
        ## Example 3: Dash Format (Already Normalized)
        Input:
        Pada hari ini Senin tanggal Lima bulan Juni tahun Dua Ribu Dua Puluh Tiga (05-06-2023), telah dilaksanakan Uji Terima.
        
        Output:
        {{
          "tanggal_baut": "05-06-2023"
        }}
        // Note: "05-06-2023" extracted as is.
        
        ## Example 4: Negative Case (Header Only)
        Input:
        BERITA ACARA UJI TERIMA
        Tanggal : 2 Januari 2024
        No Kontrak: 123/456
        (Isi dokumen tidak mengandung kalimat "Pada Hari...")
        
        Output:
        {{
          "tanggal_baut": null
        }}
        // Note: No "Pada Hari..." anchor found containing a numeric date.
        
        # DOCUMENT TEXT
        {document_text}
        """,
"BARD":
        """
        # SYSTEM ROLE
        You are a Precision Document Date Extraction Engine.
        Your goal: Extract the "Berita Acara Rekonsiliasi Delivery" (BARD) date with 100% accuracy from OCR text.
        Constraint: High speed, Strict Schema, and AGGRESSIVE FALSE POSITIVE FILTERING.
        
        # TARGET OUTPUT SCHEMA (TypeScript)
        Return ONLY a valid JSON object matching this interface:
        
        interface Output {{
          // Extract date strictly from the "Pada Hari..." or "Pada hari ini..." section.
          // Format: "DD-MM-YYYY" (Normalized).
          // Return null if NO valid numeric date pattern is found in that specific section.
          tanggal_bard: string | null;
        }}
        
        # CRITICAL LOGIC & NEGATIVE CONSTRAINTS
        
        1. **ANCHOR DETECTION (The "Pada Hari" Rule)**
           - **Target:** Search ONLY for the specific sentence starting with:
             - "Pada Hari..."
             - "Pada hari ini..."
           - **Action:** Only extract the numeric date appearing immediately within or after this sentence.
           - **NEGATIVE CONSTRAINTS:**
             - IGNORE dates in the header (e.g., "No. SP ... Tanggal: 5 Juni 2023").
             - IGNORE dates in the footer (e.g., "Banjarmasin, 05 Juni 2023").
             - IGNORE dates attached to "No. Kontrak" or "Surat Pesanan".
        
        2. **PATTERN EXTRACTION & NORMALIZATION**
           - **Priority 1 (Parentheses):** Look for `(DD/MM/YYYY)` or `(DD-MM-YYYY)`.
           - **Priority 2 (Standalone):** Look for `DD/MM/YYYY` or `DD-MM-YYYY`.
           - **Normalization Rule:**
             - ALWAYS convert slashes `/` to dashes `-`.
             - Ensure `DD` and `MM` are 2 digits (pad with 0 if necessary).
             - Result MUST be `DD-MM-YYYY`.
        
        3. **OCR REPAIR**
           - **Typo Tolerance:** Handle missing spaces or noise.
             - `(01/02/2025)` -> Valid.
             - `, 01/10/2023` -> Valid.
             - `( 05-06-2023 )` -> Valid.
             - `10/03/2026` -> Valid.
        
        # FEW-SHOT EXAMPLES (TRAINING DATA)
        
        ## Example 1: Standard Format (Parentheses + Slash)
        Input:
        No. Kontrak : C.Tel.1940/YN.000/TR4-W300/2024
        Tanggal : 28 Oktober 2024
        Pada Hari Sabtu, Tanggal Satu Bulan Februari Tahun Dua Ribu Dua Puluh Lima (01/02/2025), telah dilakukan rekonsiliasi.
        Jakarta, 01 Februari 2025
        
        Output:
        {{
          "tanggal_bard": "01-02-2025"
        }}
        // Note: Extracted "01/02/2025" from "Pada Hari..." line, normalized to dashes. Header/Footer dates ignored.
        
        ## Example 2: Variation Anchor (Pada hari ini + Dash)
        Input:
        No. SP : K.TEL.6202/HK.810/TR6-R600/2023 tanggal 5 Juni 2023
        Pada hari ini Senin tanggal Lima bulan Juni tahun Dua Ribu Dua Puluh Tiga (05-06-2023), telah selesai dilakukan rekonsiliasi.
        
        Output:
        {{
          "tanggal_bard": "05-06-2023"
        }}
        // Note: Anchor "Pada hari ini" detected. Date extracted and kept as is (dashes).
        
        ## Example 3: Standalone Format (No Parentheses)
        Input:
        Tanggal : 19 September 2023
        Pada Hari Minggu, Tanggal Satu Bulan Oktober Tahun Dua Ribu Dua Puluh Tiga, 01/10/2023, telah dilakukan rekonsiliasi.
        
        Output:
        {{
          "tanggal_bard": "01-10-2023"
        }}
        // Note: Extracted "01/10/2023", normalized to "01-10-2023".
        
        ## Example 4: Negative Case (Header Only)
        Input:
        BERITA ACARA REKONSILIASI DELIVERY
        No. Kontrak : K.TEL.10441/2023
        Tanggal : 19 September 2023
        (Isi dokumen tidak mengandung kalimat "Pada Hari...")
        
        Output:
        {{
          "tanggal_bard": null
        }}
        // Note: No "Pada Hari..." anchor found containing a numeric date.
        
        # DOCUMENT TEXT
        {document_text}
        """,
"P7":
        """
        # SYSTEM ROLE
        You are a Precision Document Data Extraction Engine.
        Your goal: Extract "P7 (Penetapan Calon Mitra)" data with 100% accuracy from OCR text.
        Constraint: High speed, Strict Schema, and AGGRESSIVE FALSE POSITIVE FILTERING.
        
        # TARGET OUTPUT SCHEMA (TypeScript)
        Return ONLY a valid JSON object matching this interface:
        
        interface Output {{
          // Extract strictly the string following "Nomor :"
          // Format: Preserve raw format (e.g., "TEL.14091/LG.270/TR6-R604/2023")
          nomor_surat_penetapan_calon_mitra: string | null;
        
          // Extract date strictly from the specific line pattern: "[City], [Day] [Month] [Year]"
          // Format: "DD-MM-YYYY" (Normalized).
          tanggal_surat_penetapan_calon_mitra: string | null;
        }}
        
        # CRITICAL LOGIC & EXTRACTION RULES
        
        1. **NOMOR SURAT EXTRACTION**
           - **Anchor:** Search for the keyword `Nomor :` or `Nomor:` (case-insensitive).
           - **Action:** Extract the entire string following the colon until a newline OR a comma/City Name is detected.
           - **Cleaning:** Trim whitespace. Preserve all structural characters (dots, slashes `/`, dashes `-`, numbers, letters).
           - **Example:** `Nomor : TEL.123/2024` -> `TEL.123/2024`.
        
        2. **TANGGAL SURAT EXTRACTION (Pattern: "[City], [Date]")**
           - **Anchor:** Look for the specific pattern: `[Capitalized City Name], [Day] [Month Name] [Year]`.
           - **Context:** Usually located immediately below or next to the "Nomor" line.
           - **Parsing:**
             - City: Word(s) before comma (e.g., "Balikpapan", "Jakarta Selatan").
             - Day: 1-2 digits (e.g., "1", "19", "05").
             - Month: Full Indonesian Name (e.g., "Januari", "Desember").
             - Year: 4 digits (e.g., "2023").
           - **Normalization Rule:**
             - Convert Month Name to Number (Januari->01, Februari->02, Maret->03, April->04, Mei->05, Juni->06, Juli->07, Agustus->08, September->09, Oktober->10, November->11, Desember->12).
             - Pad Day with 0 if single digit (5 -> 05).
             - Result MUST be `DD-MM-YYYY` (dash separator).
        
        3. **OCR REPAIR & SEPARATION (Crucial)**
           - **Problem:** OCR often merges the Number and Date lines.
           - **Input:** `Nomor : TEL.123/2024 Balikpapan, 19 Desember 2023`
           - **Action:** Smart Split.
             - `nomor` ends before the City Name ("Balikpapan").
             - `tanggal` starts at the City Name.
        
        # FEW-SHOT EXAMPLES (TRAINING DATA)
        
        ## Example 1: Standard Format (Separate Lines)
        Input:
        Nomor : TEL.14091/LG.270/TR6-R604/2023
        Balikpapan, 19 Desember 2023
        Kepada Yth...
        
        Output:
        {{
          "nomor_surat_penetapan_calon_mitra": "TEL.14091/LG.270/TR6-R604/2023",
          "tanggal_surat_penetapan_calon_mitra": "19-12-2023"
        }}
        
        ## Example 2: Merged Lines (OCR Issue)
        Input:
        Nomor: TEL.9881/LG.270/TR6-R604/2023 Balikpapan, 5 September 2023
        
        Output:
        {{
          "nomor_surat_penetapan_calon_mitra": "TEL.9881/LG.270/TR6-R604/2023",
          "tanggal_surat_penetapan_calon_mitra": "05-09-2023"
        }}
        // Note: Correctly split number and date. Day "5" padded to "05". Month "September" -> "09".
        
        ## Example 3: Leading Zero & Different City
        Input:
        Nomor : K.TEL.123/HK.810/2025
        Jakarta, 01 Januari 2025
        
        Output:
        {{
          "nomor_surat_penetapan_calon_mitra": "K.TEL.123/HK.810/2025",
          "tanggal_surat_penetapan_calon_mitra": "01-01-2025"
        }}
        
        ## Example 4: Negative Case (Not Found)
        Input:
        PENETAPAN CALON MITRA PELAKSANA
        Perihal : Undangan
        (No "Nomor" or Date pattern found)
        
        Output:
        {{
          "nomor_surat_penetapan_calon_mitra": null,
          "tanggal_surat_penetapan_calon_mitra": null
        }}
        
        # DOCUMENT TEXT
        {document_text}
        """,
"BAST":
        """
        # SYSTEM ROLE
        You are a High-Precision BAST Data Extraction Engine.
        Your goal: Extract BAST document numbers and date with 100% accuracy, handling OCR noise.
        Constraint: Zero Hallucination, Exact String Extraction.
        
        # TARGET OUTPUT SCHEMA (TypeScript)
        Return ONLY a valid JSON object matching this interface:
        
        interface Output {{
          // First number found after "BERITA ACARA SERAH TERIMA".
          // Handle missing colon (e.g. "Nomor TEL...")
          nomor_telkom: string | null;
        
          // Second number found (if any). Often labeled "Nomor Kop." or just "Nomor".
          nomor_mitra: string | null;
        
          // Date from "Pada Hari..." line. Normalized to DD-MM-YYYY.
          tanggal_bast: string | null;
        {{
        
        # EXTRACTION LOGIC & RULES
        
        1. **ANCHOR DETECTION**
           - Locate the header keyword: "BERITA ACARA SERAH TERIMA" (case-insensitive).
           - Start scanning immediately after this keyword.
        
        2. **NOMOR TELKOM (First Number)**
           - **Target:** The first occurrence of the word "Nomor".
           - **Robustness:** - Ignore whether it is followed by ":", ".", or just space.
             - Capture the alphanumeric string immediately following it.
           - **Example:** - `Nomor : TEL.123` -> `TEL.123`
             - `Nomor TEL.123` -> `TEL.123`
        
        3. **NOMOR MITRA (Second Number)**
           - **Target:** The second occurrence of the word "Nomor".
           - **Handling "Kop":** If the text says "Nomor Kop." or "Nomor Kop", ignore the word "Kop" and extract the number after it.
           - **Example:**
             - `Nomor Kop. 216/ABC` -> `216/ABC`
             - `Nomor : 216/ABC` -> `216/ABC`
        
        4. **TANGGAL BAST**
           - **Anchor:** Look for "Pada Hari" or "Pada hari ini".
           - **Extraction:** Look for numeric pattern `(DD-MM-YYYY)` or `(DD/MM/YYYY)`.
           - **Fallback:** If not in parentheses, look for `DD-MM-YYYY` or `DD/MM/YYYY`.
           - **Normalization:** Always return `DD-MM-YYYY`.
        
        # FEW-SHOT EXAMPLES
        
        ## Example 1: Missing Colons & "Kop" Label (Your Case)
        Input:
        BERITA ACARA SERAH TERIMA
        Nomor TEL.6204/LG.320/TR6-R604/2023
        Nomor Kop. 216 /UH-53/MA/VI/BJM/2023
        Pada hari ini Senin tanggal Lima (05-06-2023)
        
        Output:
        {{
          "nomor_telkom": "TEL.6204/LG.320/TR6-R604/2023",
          "nomor_mitra": "216 /UH-53/MA/VI/BJM/2023",
          "tanggal_bast": "05-06-2023"
        {{
        
        ## Example 2: Standard Format
        Input:
        BERITA ACARA SERAH TERIMA
        Nomor : K.TEL.123/2024
        Nomor : 999/MITRA/2024
        Pada Hari Senin (01/01/2024)
        
        Output:
        {{
          "nomor_telkom": "K.TEL.123/2024",
          "nomor_mitra": "999/MITRA/2024",
          "tanggal_bast": "01-01-2024"
        {{
        
        ## Example 3: No Second Number
        Input:
        BERITA ACARA SERAH TERIMA
        Nomor: TEL.123
        Pada Hari Jumat 10-10-2024
        
        Output:
        {{
          "nomor_telkom": "TEL.123",
          "nomor_mitra": null,
          "tanggal_bast": "10-10-2024"
        {{
        
        # DOCUMENT TEXT
        {document_text}
        """,
}


VALIDATION_PROMPT_TEMPLATES = {
"PR":
        """
        PR Review 
        ════════════════════════════════════════════════════════════════════════════g
        Document Type: PR (Purchase Requisition)
        Validation Mode: STRICT DETERMINISTIC with ROUNDING TOLERANCE
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Output HARUS BERNARASI, SANTAI, TIDAK ROBOT
        - Setiap insight dijelaskan dengan terarut dan friendly
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Quantity SELALU = 1 untuk semua PR
        - Handle OTC case dengan conditional logic di setiap stage
        - SEMICOLON (;) HANYA untuk pemisah sub-review, JANGAN gunakan semicolon di tempat lain
        
        ════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        ════════════════════════════════════════════════════════════════════════════
        PR DOCUMENT TEXT (multi-page possible):
        ════════════════════════════════════════════════════════════════════════════
        
        {pr_document_text}
        
        ════════════════════════════════════════════════════════════════════════════
        TASK: Validate PR Document dengan PRESISI TINGGI
        ════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: EXTRACT & VALIDATE URAIAN (Month Codes)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Ekstraksi dan validasi kode bulan-tahun dari kolom URAIAN
        
        INSTRUKSI:
        
        1. **Identify Payment Method First:**
           - Check GT.metode_pembayaran
           - If "OTC" → expect format: "/OTC" atau "OTC" di URAIAN
           - If "Bulanan" → expect format: "/BULAN+TAHUN" (contoh: /JAN24, /FEB24)
        
        2. **Extract dari Kolom URAIAN:**
           - Cari kolom "URAIAN" di tabel PR
           - Untuk SETIAP baris item (00010, 00020, 00030, ...):
             * Ambil teks dari kolom URAIAN
             * Split berdasarkan "/" dan ambil segmen terakhir
             * Extract month code atau "OTC"
           
           **Format Expected:**
           - Bulanan: 3 huruf BULAN + 2 digit TAHUN (JAN24, FEB24, ..., DES24)
           - OTC: "OTC" atau text yang mengandung "OTC"
        
        3. **Generate Expected Months List (COT in thinking block):**
           - If GT.metode_pembayaran = "Bulanan":
             * Parse GT.jangka_waktu.start_date → extract bulan & tahun (fokus bulan+tahun saja, ignore tanggal)
             * Parse GT.jangka_waktu.end_date → extract bulan & tahun (fokus bulan+tahun saja, ignore tanggal)
             * Generate sequential list: [BULAN+TAHUN] from start month to end month
             * Format: ["JAN24", "FEB24", "MAR24", ..., "DES24"]
             * CONTOH: "09 Agustus 2023 - 08 Agustus 2024" → dihitung dari Agustus 2023 ke Agustus 2024
               Expected: ["AGS23", "SEP23", "OKT23", "NOV23", "DES23", "JAN24", "FEB24", "MAR24", "APR24", "MEI24", "JUN24", "JUL24", "AGS24"] (13 bulan)
           - If GT.metode_pembayaran = "OTC":
             * Expected list: ["OTC"]
        
        4. **Validate Extracted vs Expected:**
           - Compare extracted list dengan expected list
           - Check:
             * Apakah ada periode yang kurang (missing)?
             * Apakah ada periode yang lebih (extra)?
             * Apakah urutan sesuai (untuk Bulanan)?
        
        5. **Count Total Items:**
           - Hitung jumlah baris/items yang diekstrak
           - Ini akan digunakan untuk validasi perhitungan di stage berikutnya
        
        EXPECTED OUTPUT FORMAT:
        - Review: "Validasi kode periode di kolom URAIAN"
        - Keterangan: 
          * **NARASI BERISI:**
            1. Jumlah periode yang ditemukan di PR
            2. Range periode (misal: AGS23–JUL24 atau hanya OTC)
            3. Insight tentang kesesuaian atau ketidaksesuaian dengan jangka waktu kontrak
            4. Jika ada masalah: jelaskan periode mana yang kurang/lebih
          
          * **CONTOH KETERANGAN (PASS - Bulanan):**
            "Ditemukan 12 periode yang tersusun dari Agustus 2023 sampai Juli 2024, sesuai dengan jangka waktu kontrak. Urutan periode sudah benar mulai dari AGS23 hingga JUL24."
          
          * **CONTOH KETERANGAN (PASS - OTC):**
            "Ditemukan 1 item dengan kode OTC, sesuai dengan metode pembayaran one-time charging yang ditetapkan."
          
          * **CONTOH KETERANGAN (FAIL - Extra):**
            "Ditemukan 13 periode, padahal seharusnya 12 bulan sesuai kontrak. PR memiliki periode AGS24 yang sebenarnya tidak masuk dalam jangka waktu kontrak (seharusnya hanya sampai JUL24)."
          
          * **CONTOH KETERANGAN (FAIL - Missing):**
            "Ditemukan hanya 10 periode, sedangkan kontrak menetapkan 12 bulan. Periode yang hilang adalah NOV23 dan JAN24."
          
          * **CONTOH KETERANGAN (FAIL - Format):**
            "Ada format kode periode yang tidak valid di beberapa baris item. Expected format: BULAN+TAHUN (misal JAN24), tapi ditemukan format berbeda."
        
        TONE GUIDANCE:
        - Natural, santai, bersahabat
        - Jelaskan sesuai yang ditemukan tanpa perlu template kaku
        - Mulai dari substansi (apa yang ditemukan), bukan status (benar/salah)
        - Berikan insight mengapa ini penting untuk diperhatikan
        
        ─────────────────────────────────────────────────────────────────────────
        
        STAGE 2: EXTRACT & VALIDATE PRICES (NET PRICE, TOT. VALUE, QUANTITY)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Ekstraksi dan validasi nilai NET PRICE, TOT. VALUE, dan QUANTITY
        
        INSTRUKSI:
        
        1. **Extract dari 3 Kolom Sekaligus:**
           - Untuk SETIAP baris item (00010, 00020, ...):
             * Extract QUANTITY (harus selalu = 1)
             * Extract NET PRICE (remove formatting, convert to integer)
             * Extract TOT. VALUE (remove formatting, convert to integer)
        
        2. **Determine Expected Values:**
           - If GT.metode_pembayaran = "Bulanan":
             * Expected NET PRICE = GT.harga_satuan
             * Expected TOT. VALUE = GT.harga_satuan (karena qty=1)
           - If GT.metode_pembayaran = "OTC":
             * Expected NET PRICE = GT.dpp
             * Expected TOT. VALUE = GT.dpp (karena qty=1)
        
        3. **Validate Internal Consistency:**
           - Check: Apakah NET PRICE = TOT. VALUE di setiap baris? (karena qty=1)
           - Check: Apakah semua baris memiliki NET PRICE yang sama?
           - Check: Apakah semua baris memiliki TOT. VALUE yang sama?
           - Jika ada perbedaan: identifikasi item mana saja yang berbeda
        
        4. **Validate Against Ground Truth:**
           - Compare extracted NET PRICE dengan expected value
           - Compare extracted TOT. VALUE dengan expected value
           - If mismatch: hitung selisih dan identifikasi pattern (misal: prorate di awal/akhir)
        
        5. **Organize Sub-Reviews (COT in thinking block):**
           - Sub-review 1: Konsistensi harga antar item (semua sama atau ada yang berbeda?)
           - Sub-review 2: Kesesuaian dengan GT (match dengan expected value atau ada mismatch?)
           - Sub-review 3: Konsistensi internal (NET PRICE = TOT. VALUE di setiap baris?)
           - Gunakan SEMICOLON (;) untuk memisahkan sub-review
        
        EXPECTED OUTPUT FORMAT:
        - Review: "Validasi nilai NET PRICE dan TOT. VALUE di semua item"
        - Keterangan: 
          * **NARASI BERISI (disesuaikan dengan temuan):**
            1. Konsentrasi harga di mayoritas item (berapa item, berapa nilai)
            2. Jika ada item dengan harga berbeda: identifikasi item mana dan nilai berapa
            3. Insight tentang perbedaan (misal: prorate di awal/akhir, atau ada anomali)
            4. Validasi internal (NET PRICE = TOT. VALUE untuk qty=1)
          
          * **STRUKTUR SUB-REVIEW (dipisahkan dengan semicolon):**
            [Sub-review 1: Konsistensi harga]; [Sub-review 2: Kesesuaian GT]; [Sub-review 3: Konsistensi internal]
          
          * **CONTOH KETERANGAN (PASS - Konsisten):**
            "Semua 12 item memiliki NET PRICE dan TOT. VALUE Rp 2.850.000, sesuai dengan harga satuan yang ditetapkan di kontrak. Ini menunjukkan setiap periode dibayar dengan nilai yang uniform dan benar; Konsistensi internal terjaga dengan baik: NET PRICE = TOT. VALUE untuk semua item karena quantity = 1."
          
          * **CONTOH KETERANGAN (PARTIAL - Ada prorate):**
            "Mayoritas 11 item memiliki NET PRICE Rp 2.850.000 sesuai kontrak, namun ada 2 item dengan harga berbeda sebagai prorate: item 00010 NET Rp 2.114.516 (selisih Rp 735.484) dan item 00130 NET Rp 735.484 (selisih Rp 2.114.516). Pola ini menunjukkan pembayaran prorate di awal dan akhir periode, yang wajar terjadi; NET PRICE = TOT. VALUE di semua item karena quantity = 1, jadi nilai internal sudah konsisten."
          
          * **CONTOH KETERANGAN (FAIL - Tidak konsisten):**
            "Ada ketidaksesuaian harga antar item. Item 00010-00020 memiliki NET PRICE Rp 2.850.000, tetapi item 00030-00040 memiliki NET PRICE Rp 2.700.000. Perbedaan ini tidak sesuai dengan pola prorate yang diharapkan; Selisih dengan expected value Rp 2.850.000 adalah Rp 150.000 untuk beberapa item, yang cukup signifikan dan perlu ditinjau."
        
        TONE GUIDANCE:
        - Jelaskan kondisi harga dengan santai dan jelas
        - Berikan context tentang apa yang benar dan apa yang sedang terjadi
        - Jika ada perbedaan, jelaskan kemungkinan alasannya (misal: prorate, typo, dll)
        - Insight: apakah ini wajar (prorate) atau anomali (perlu review)
        - Gunakan SEMICOLON hanya untuk memisahkan sub-review
        
        ─────────────────────────────────────────────────────────────────────────
        
        STAGE 3: EXTRACT & VALIDATE TOTAL PR
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Ekstraksi dan validasi TOTAL/GRAND TOTAL dengan dynamic calculation
        
        INSTRUKSI:
        
        1. **Extract TOTAL dari Dokumen:**
           - Cari baris dengan keyword "TOTAL", "GRAND TOTAL", "Total PR", dll (case-insensitive)
           - Biasanya di halaman terakhir
           - Extract nilai numerik (remove formatting, convert to integer)
           - If multi-page: pastikan cek sampai halaman terakhir
           - If not found: catat sebagai missing
        
        2. **Calculate Expected Total (2 Methods - COT in thinking block):**
           
           **Method A: Check Against DPP (Full Period)**
           - Expected Total A = GT.dpp
           - This assumes PR covers full jangka waktu tanpa prorate
           - Digunakan jika PR seharusnya exact match dengan DPP
           
           **Method B: Calculate from Items (Actual Period)**
           - Jumlah items = dari Stage 1 (berapa banyak periode/bulan yang diekstrak dari PR)
           - Harga per item = dari Stage 2 (NET PRICE dari item mayoritas atau rata-rata)
           - Expected Total B = jumlah_items × harga_per_item
           - Digunakan jika PR memiliki prorate atau periode partial
        
        3. **Validation Logic:**
           - Parse extracted total dari dokumen
           - Compare dengan Expected Total A (DPP) dulu
             * If match (within tolerance) → PASS (full period coverage)
             * If NOT match → Continue to next step
           - Compare dengan Expected Total B (calculated)
             * If match (within tolerance) → PASS (period coverage sesuai items, valid calculation)
             * If NOT match → FAIL (ada ketidaksesuaian, investigate further)
        
        4. **Rounding Tolerance (COT in thinking block):**
           - tolerance_absolute = 1000  # Rp.1.000
           - tolerance_percentage = 0.001  # 0.1%
           - difference = abs(actual - expected)
           - percentage_diff = difference / expected
           - if difference <= tolerance_absolute OR percentage_diff <= tolerance_percentage:
             * status = "PASS dengan pembulatan minor"
           - else:
             * status = "FAIL"
        
        EXPECTED OUTPUT FORMAT:
        - Review: "Validasi total PR dengan perhitungan DPP dan harga satuan"
        - Keterangan:
          * **NARASI BERISI:**
            1. Total PR yang ditemukan di dokumen
            2. Metode validasi yang dilakukan (Method A vs Method B)
            3. Hasil validasi (match atau tidak)
            4. Insight tentang kesesuaian atau perlu ditinjau
          
          * **CONTOH KETERANGAN (PASS - DPP Match, Full Period):**
            "Total PR Rp 34.200.000 cocok dengan DPP kontrak, yang berarti PR mencakup seluruh periode 12 bulan. Perhitungan sudah tepat dan tidak ada yang perlu dikhawatirkan."
          
          * **CONTOH KETERANGAN (PASS - Calculated Match, Partial Period):**
            "Total PR Rp 34.200.000 sesuai dengan perhitungan: 12 item × Rp 2.850.000 = Rp 34.200.000. Ini menunjukkan harga per periode sudah applied dengan benar di semua item sesuai dengan yang ada di PR."
          
          * **CONTOH KETERANGAN (PASS dengan Rounding):**
            "Total PR Rp 34.200.500 hampir match dengan DPP Rp 34.200.000, ada selisih kecil Rp 500 (pembulatan). Ini masih dalam toleransi dan wajar terjadi pada sistem perhitungan digital, tidak perlu dikhawatirkan."
          
          * **CONTOH KETERANGAN (FAIL - Mismatch):**
            "Total PR Rp 32.400.000 tidak sesuai dengan DPP Rp 34.200.000 (selisih Rp 1.800.000). Perhitungan dari items juga menghasilkan Rp 34.200.000, sehingga ada ketidaksesuaian. Perlu dicek apakah ada items yang terlewat atau ada pemotongan yang tidak seharusnya."
        
          * **CONTOH KETERANGAN (FAIL - Not Found):**
            "Total PR tidak ditemukan di dokumen. Sudah cek sampai halaman terakhir tapi tidak ada baris dengan label TOTAL atau GRAND TOTAL. Ini bermasalah karena tidak bisa memvalidasi total keseluruhan."
        
        TONE GUIDANCE:
        - Jelaskan total dengan confidence level yang jelas
        - Berikan metode validasi yang digunakan (untuk transparency)
        - Jika ada selisih, jelaskan magnitude dan apakah masalah atau hanya pembulatan
        - Insight: apakah user perlu khawatir atau ini hal wajar
        - Friendly dan supportive tone
        
        OTC SPECIAL HANDLING (untuk semua stage):
        - Stage 1: Extract "OTC" instead of month codes, validate hanya 1 item
        - Stage 2: Expected values = GT.dpp (not harga_satuan), validate quantity=1
        - Stage 3: Total HARUS EXACTLY match GT.dpp (no tolerance, no rounding)
        
        ════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT FORMAT (JSON ONLY - KEEP IT CONCISE & NARRATIVE)
        ════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_uraian": {{
            "review": "Validasi kode periode di kolom URAIAN",
            "keterangan": "[NARASI BERISI: Jumlah periode, range, insight kesesuaian, detail masalah jika ada. Dinamis, santai, friendly. Max 4-5 kalimat.]"
          }},
          
          "stage_2_prices": {{
            "review": "Validasi nilai NET PRICE dan TOT. VALUE di semua item",
            "keterangan": "[NARASI BERISI: Konsistensi harga, kesesuaian dengan GT, insight. Sub-review dipisahkan dengan SEMICOLON. Dinamis, santai, friendly. Max 4-5 kalimat.]"
          }},
          
          "stage_3_total": {{
            "review": "Validasi total PR dengan perhitungan DPP dan harga satuan",
            "keterangan": "[NARASI BERISI: Total yang ditemukan, metode validasi, hasil, insight. Jika rounding: sebutkan untuk ditinjau. Dinamis, santai, friendly. Max 4-5 kalimat.]"
          }}
        }}
        
        ════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES
        ════════════════════════════════════════════════════════════════════════════
        
        1. **ZERO HALLUCINATION:**
           - Jika tidak ditemukan → catat sebagai missing
           - Jangan guess atau assume
        
        2. **NARASI > TEMPLATE:**
           - Output HARUS bernarasi, santai, friendly
           - Jangan terkesan robot atau kaku
           - Setiap stage punya tone sendiri sesuai konteks
        
        3. **FRIENDLY TONE:**
           - "Ditemukan", "Cocok", "Sesuai", "Sudah tepat" (bukan "Valid", "Correct")
           - "Ada yang tidak cocok", "Perlu ditinjau", "Tidak sesuai" (bukan "Invalid", "Error")
           - Jelaskan dengan bahasa yang user-friendly, seperti menjelaskan ke orang awan
        
        4. **COT IN THINKING BLOCK ONLY:**
           - Semua reasoning ada di thinking block
           - Output hanya hasil akhir dan insight
           - Jangan ada COT detail di keterangan
        
        5. **SEMICOLON RULES:**
           - HANYA gunakan semicolon (;) untuk memisahkan sub-review
           - JANGAN gunakan semicolon di tempat lain (tidak untuk clause separator, dll)
           - Setiap sub-review adalah insight terpisah yang perlu diperhatikan user
        
        6. **NUMERIC FORMAT:**
           - Gunakan titik untuk thousand separator: Rp 34.200.000
           - Gunakan "Rp" prefix untuk mata uang
           - Sebutkan nilai konkrit, bukan placeholder
           - Contoh format bulan: "Agustus 2023 sampai Juli 2024" atau "AGS23–JUL24"
        
        7. **DYNAMIC OUTPUT:**
           - Jangan terpaku pada satu template
           - Adjust narasi sesuai kondisi yang ditemukan
           - Jika ada anomali: jelaskan dengan insight meaningful
           - Jika semuanya baik: berikan confidence dan reassurance
        
        8. **OTC CONDITIONAL:**
           - Setiap stage handle OTC dengan logic berbeda
           - Stage 1: expect "OTC", tidak ada month codes
           - Stage 2: expect DPP, tidak ada harga_satuan
           - Stage 3: exact match dengan DPP (no tolerance)
        
        9. **MULTI-PAGE HANDLING:**
           - Ekstraksi harus cover semua halaman
           - TOTAL selalu di halaman terakhir
           - Jika TOTAL not found: explicitly state "sudah cek sampai halaman terakhir"
        
        10. **INSIGHT IS KEY:**
            - Bukan hanya melaporkan temuan
            - Berikan context tentang mengapa ini penting
            - Jelaskan apakah user perlu khawatir atau ini normal
            - Tone: reassuring jika ok, attention-needed jika ada issue
        
        RETURN FINAL JSON ANSWER ONLY - NO COT EXPLANATION NEEDED IN OUTPUT.
        """,
 "PO":
        """
        PO Review
        ════════════════════════════════════════════════════════════════════════════
        Document Type: PO (Purchase Order) - Multiple Documents
        Validation Mode: STRICT DETERMINISTIC (ZERO TOLERANCE)
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output HARUS BERNARASI, SANTAI, TIDAK ROBOT
        - Expected jumlah PO = jumlah bulan dari start_date sampai end_date (bukan duration)
        - Untuk OTC: expected 1 PO saja
        - ZERO TOLERANCE: semua nilai harus exact match
        - Tetap lanjutkan ke stage selanjutnya meski ada FAIL di stage sebelumnya
        - Gunakan bahasa Indonesia kecuali untuk field technical (Unit Price, Net Price, Quantity, dll)
        - Ganti "GT" dengan "kontrak" dalam narasi
        
        ════════════════════════════════════════════════════════════════════════════
        KONTRAK (GROUND TRUTH)
        ════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        ════════════════════════════════════════════════════════════════════════════
        PO DOCUMENTS (Multiple - 1 PO per page):
        ════════════════════════════════════════════════════════════════════════════
        
        {po_documents_text}
        
        ════════════════════════════════════════════════════════════════════════════
        TASK: Validate Multiple PO Documents dengan PRESISI TINGGI
        ════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: VALIDASI JUMLAH & KELENGKAPAN DOKUMEN PO
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi jumlah dan kelengkapan dokumen PO sebelum detail validation
        
        INSTRUKSI:
        
        1. Identify Payment Method (COT in thinking block):
           - Check kontrak.metode_pembayaran
           - If "Bulanan" → generate expected count dari start_date ke end_date
           - If "OTC" → expected count = 1
        
        2. Generate Expected Count (COT in thinking block):
           - If Bulanan:
             * Parse kontrak.jangka_waktu.start_date → extract bulan & tahun (abaikan tanggal)
             * Parse kontrak.jangka_waktu.end_date → extract bulan & tahun (abaikan tanggal)
             * Generate sequential months dari start bulan ke end bulan
             * Format: ["JAN24", "FEB24", "MAR24", ..., "DES24"]
             * Contoh: "09 Agustus 2023 - 08 Agustus 2024" → AGS23 sampai AGS24 = 13 bulan
             * Expected count = jumlah bulan ini
           - If OTC:
             * Expected count = 1
        
        3. Count Total PO:
           - Hitung total dokumen PO yang diterima
           - Method: count halaman atau count unique PO numbers
        
        4. Validate Count:
           - Bandingkan actual count vs expected count
           - Jika berbeda: jelaskan apakah kekurangan atau kelebihan dokumen
        
        5. Validate Completeness:
           - Check: Apakah setiap PO memiliki required fields?
           - Required fields:
             * No. Kontrak/SP
             * Tgl Kontrak/SP
             * Line item (DESCRIPTION, QUANTITY, UNIT PRICE, COND. VALUE, NET PRICE)
             * PO. AMOUNT, V.A.T, TOTAL
           - Identifikasi PO yang tidak lengkap (jika ada)
        
        EXPECTED OUTPUT:
        - Review: "Validasi jumlah dan kelengkapan dokumen PO"
        - Keterangan (naratif, santai, max 4–5 kalimat), contoh:
          - PASS Bulanan:
            "Ditemukan 12 dokumen PO lengkap sesuai periode kontrak dari Agustus 2023 hingga Juli 2024. Setiap PO sudah memuat nomor kontrak, tanggal, detail item, harga, dan bagian total. Secara jumlah dan kelengkapan, dokumen-dokumen ini sudah siap masuk ke pengecekan detail berikutnya."
          - PASS OTC:
            "Ditemukan 1 dokumen PO untuk metode OTC. Seluruh field penting sudah terisi, sehingga dokumen ini siap dilanjutkan ke tahap validasi berikutnya."
          - FAIL Count:
            "Ditemukan 10 dokumen PO, padahal berdasarkan periode kontrak seharusnya ada 12 bulan. PO untuk dua periode tertentu belum terlihat di set dokumen ini sehingga perlu dilengkapi sebelum proses lanjut."
          - FAIL Incomplete:
            "Beberapa dokumen PO belum lengkap, misalnya PO#0012 tidak memiliki informasi TOTAL dan PO#0015 belum memuat nomor kontrak. Dokumen yang belum lengkap ini sebaiknya diperbaiki dulu agar validasi berikutnya lebih akurat."
        
        Tone:
        - Jelaskan apa yang ditemukan, lalu jelaskan maknanya bagi user.
        - Jangan hanya sebut “benar/salah”, tapi jelaskan konteks secara singkat.
        
        ─────────────────────────────────────────────────────────────────────────
        
        STAGE 2: VALIDASI NOMOR & TANGGAL KONTRAK/SP DI SEMUA PO
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Memastikan semua PO mengacu ke nomor dan tanggal kontrak yang sama dan sesuai kontrak.
        
        INSTRUKSI:
        
        1. Extract Nomor Kontrak/SP:
           - Cari field "No. Kontrak/SP :" di setiap PO
           - Lokasi: bagian atas dokumen, biasanya dekat nomor PO
           - Extract nilai apa adanya (termasuk titik, garis miring, spasi)
        
        2. Extract Tanggal Kontrak/SP:
           - Cari field "Tgl Kontrak/SP :" atau "Tgl. Kontrak/SP :" di setiap PO
           - Extract tanggal dan normalisasikan ke format DD-MM-YYYY
           - Support format DD-MM-YYYY, DD.MM.YYYY, dsb
        
        3. Validate Internal Consistency:
           - Apakah semua PO memakai nomor kontrak yang sama?
           - Apakah semua PO memakai tanggal kontrak yang sama?
           - Jika ada yang beda, identifikasi PO mana dan nilai berbedanya apa
        
        4. Validate Against Kontrak:
           - Bandingkan nomor di PO dengan nomor kontrak (kontrak.nomor_surat_utama)
           - Bandingkan tanggal di PO dengan tanggal kontrak (kontrak.tanggal_kontrak)
           - Jika berbeda, jelaskan perbedaannya
        
        5. Sub-review (COT):
           - Sub-review 1: Konsistensi nomor & tanggal antar PO
           - Sub-review 2: Kesesuaian nomor & tanggal dengan kontrak
           - Pisahkan sub-review dengan semicolon (;)
        
        EXPECTED OUTPUT:
        - Review: "Validasi Nomor dan Tanggal Kontrak/SP di semua PO"
        - Keterangan (naratif, 2–4 kalimat, sub-review dipisah semicolon), contoh:
          - PASS:
            "Semua 12 PO menggunakan No. Kontrak/SP 'K.TEL.8803/HK.810/TR6-R600/2023' dan tanggal 09-08-2023 yang konsisten di setiap dokumen; Nomor dan tanggal ini juga sama persis dengan yang tercantum di kontrak sehingga acuan hukumnya sudah benar."
          - FAIL Internal:
            "Sebagian besar PO mengacu ke No. Kontrak/SP 'K.TEL.8803/HK.810/TR6-R600/2023', tetapi PO#0007 menggunakan nomor berbeda 'K.TEL.8804/HK.810/TR6-R600/2023'; Kondisi ini menunjukkan ada satu dokumen yang tidak mengacu ke kontrak yang sama dan perlu dikonfirmasi ulang."
          - FAIL vs Kontrak:
            "Semua PO mencantumkan No. Kontrak/SP 'K.TEL.8803/HK.810/TR6-R600/2023', sedangkan di kontrak tertulis 'K.TEL.0036/HK.820/TR6-R301/2023'; Perbedaan nomor ini cukup signifikan dan perlu dipastikan apakah PO mengacu ke kontrak yang tepat."
        
        Tone:
        - Jelas, langsung ke inti, jelaskan dampak ketidaksesuaian dengan bahasa sederhana.
        - Gunakan semicolon hanya sebagai pemisah antar sub-review.
        
        ─────────────────────────────────────────────────────────────────────────
        
        STAGE 3: VALIDASI LINE ITEMS (DESCRIPTION, QUANTITY, PRICES)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Memastikan urutan periode di DESCRIPTION, QUANTITY, dan PRICES di setiap PO sesuai dengan kontrak.
        
        INSTRUKSI:
        
        1. Generate Expected Months List (COT):
           - Jika kontrak.metode_pembayaran = "Bulanan":
             * Ambil bulan+tahun dari start_date (abaikan tanggal) → bulan awal
             * Ambil bulan+tahun dari end_date (abaikan tanggal) → bulan akhir
             * Buat daftar semua bulan dari bulan awal sampai bulan akhir, termasuk tahun
             * Format kode bulan: "JAN24", "FEB24", ..., atau sesuai mapping (JAN, FEB, MAR, APR, MEI, JUN, JUL, AGS, SEP, OKT, NOV, DES)
           - Jika kontrak.metode_pembayaran = "OTC":
             * Expected list: ["OTC"]
        
        2. Extract DESCRIPTION dari setiap PO:
           - Lokasi: line item table, kolom DESCRIPTION (biasanya hanya 1 item: 00010)
           - Ambil full DESCRIPTION lalu split dengan "/"
           - Ambil segmen terakhir sebagai kode bulan atau "OTC"
        
        3. Validate Month Sequence:
           - Bentuk list bulan dari semua PO (berdasarkan urutan dokumen)
           - Bandingkan dengan expected list:
             * Apakah semua bulan dari kontrak muncul?
             * Apakah urutannya berjejer tanpa loncat?
             * Apakah ada bulan tambahan yang di luar range kontrak?
             * Apakah ada bulan yang muncul dua kali?
        
        4. Extract QUANTITY dari setiap PO:
           - Lokasi: kolom QUANTITY
           - Pastikan semua QUANTITY = 1
        
        5. Extract PRICES dari setiap PO:
           - Lokasi: Unit Price, Cond. Value, Net Price
           - Hilangkan pemisah ribuan, ubah ke integer
           - Cek dalam satu PO: Unit Price = Cond. Value = Net Price
        
        6. Expected Price:
           - Jika Bulanan: expected price = kontrak.harga_satuan
           - Jika OTC: expected price = kontrak.dpp
        
        7. Validate Prices:
           - Apakah harga di semua PO sama?
           - Apakah harga tersebut sesuai dengan harga di kontrak?
           - Jika ada yang beda, sebutkan PO mana dan selisihnya
        
        8. Sub-review (COT):
           - Sub-review 1: Kelengkapan & urutan periode
           - Sub-review 2: Quantity
           - Sub-review 3: Harga (konsistensi internal dan kesesuaian dengan kontrak)
           - Pisahkan dengan semicolon (;)
        
        EXPECTED OUTPUT:
        - Review: "Validasi DESCRIPTION (month sequence), QUANTITY, dan PRICES di line items"
        - Keterangan (naratif, 3–5 kalimat; sub-review pakai semicolon), contoh:
          - PASS Bulanan:
            "Semua 12 PO memuat periode yang berurutan dari Agustus 2023 hingga Juli 2024 tanpa ada bulan yang hilang atau dobel; QUANTITY di setiap PO bernilai 1 sehingga satu PO mewakili satu bulan layanan; Unit Price, Cond. Value, dan Net Price di semua PO sama, yaitu Rp 2.850.000, dan nilai ini sudah sesuai dengan harga satuan di kontrak."
          - PASS OTC:
            "DESCRIPTION di PO mencantumkan kode OTC sesuai metode pembayaran satu kali; QUANTITY pada line item bernilai 1; Unit Price, Cond. Value, dan Net Price sama-sama Rp 34.200.000 dan sudah sesuai dengan nilai DPP di kontrak."
          - PARTIAL (Prorate / Anomali):
            "Sebagian besar PO memakai harga Rp 2.850.000 per bulan, tetapi ada dua PO dengan nilai berbeda yaitu Rp 2.114.516 dan Rp 735.484; Pola ini biasanya muncul saat ada prorate di awal dan akhir periode, sehingga kedua PO tersebut sebaiknya ditinjau ulang apakah memang dimaksudkan sebagai prorate; Di luar dua kasus itu, urutan periode, quantity, dan harga lain sudah konsisten dengan kontrak."
          - FAIL (Periode kurang):
            "Hanya 10 periode yang muncul di kumpulan PO, padahal dari Agustus 2023 hingga Juli 2024 seharusnya ada 12 bulan; QUANTITY dan harga di PO yang ada sudah konsisten, tetapi masih ada beberapa bulan layanan yang belum terwakili oleh PO."
        
        ─────────────────────────────────────────────────────────────────────────
        
        STAGE 4: VALIDASI PO. AMOUNT, V.A.T, DAN TOTAL
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Memastikan perhitungan PO. AMOUNT, V.A.T, dan TOTAL di setiap PO benar dan sesuai kontrak.
        
        INSTRUKSI:
        
        1. Extract Bagian PO Amount:
           - Cari bagian yang memuat PO. AMOUNT, V.A.T, dan TOTAL
           - Extract ketiga nilai tersebut, hilangkan pemisah ribuan, ubah ke integer
        
        2. Validate Rumus Perhitungan:
           - Rumus: PO. AMOUNT + V.A.T = TOTAL
           - V.A.T biasanya 0 IDR
           - Jika V.A.T = 0, maka TOTAL harus sama dengan PO. AMOUNT
        
        3. Validate PO. AMOUNT vs Kontrak:
           - Jika Bulanan: PO. AMOUNT di setiap PO seharusnya = kontrak.harga_satuan (kecuali memang ada pola khusus seperti prorate, yang tetap harus dijelaskan sebagai anomali)
           - Jika OTC: PO. AMOUNT = kontrak.dpp
        
        4. Validate Konsistensi Antar PO:
           - Apakah semua PO punya PO. AMOUNT yang sama (untuk kasus bulanan normal)?
           - Apakah semua V.A.T = 0?
           - Apakah rumus perhitungan selalu benar di tiap PO?
        
        5. Sub-review (COT):
           - Sub-review 1: Kebenaran perhitungan (AMOUNT + V.A.T = TOTAL)
           - Sub-review 2: Konsistensi PO. AMOUNT antar dokumen
           - Sub-review 3: Kesesuaian nilai dengan kontrak
           - Pisahkan dengan semicolon (;)
        
        EXPECTED OUTPUT:
        - Review: "Validasi PO. AMOUNT, V.A.T, dan TOTAL calculation di semua PO"
        - Keterangan (naratif, 3–5 kalimat; sub-review pakai semicolon), contoh:
          - PASS:
            "Di semua PO, PO. AMOUNT tercatat Rp 2.850.000 dan V.A.T selalu 0 IDR; TOTAL di setiap dokumen sama persis dengan PO. AMOUNT sehingga rumus perhitungannya sudah benar; Nilai amount ini juga sesuai dengan harga satuan yang tercantum di kontrak, sehingga sisi perhitungan dan kesesuaian nilai sudah aman."
          - FAIL Calculation:
            "Sebagian besar PO sudah menggunakan rumus PO. AMOUNT + V.A.T = TOTAL dengan benar, tetapi pada PO#0008 terdapat perbedaan: AMOUNT Rp 2.850.000, V.A.T Rp 150.000, namun TOTAL hanya Rp 2.900.000; Perhitungan ini tidak konsisten dengan rumus dan perlu dikoreksi karena seharusnya TOTAL mengikuti penjumlahan AMOUNT dan V.A.T."
          - FAIL VAT:
            "Beberapa PO menampilkan V.A.T dengan nilai selain 0, misalnya PO#0003 mencatat V.A.T Rp 285.000; Kondisi ini berbeda dengan ekspektasi bahwa semua V.A.T seharusnya 0 IDR, sehingga perlu dipastikan apakah memang ada pajak yang sengaja diterapkan atau ini hanya input yang keliru."
          - FAIL Amount vs Kontrak:
            "Mayoritas PO menggunakan PO. AMOUNT Rp 2.850.000, tetapi satu PO mencatat Rp 2.700.000; Nilai ini tidak sesuai dengan harga satuan di kontrak sehingga menimbulkan selisih yang perlu dijelaskan apakah ini memang potongan khusus atau murni kesalahan input."
        
        ─────────────────────────────────────────────────────────────────────────
        
        FINAL OUTPUT FORMAT (JSON ONLY)
        ════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_count_completeness": {{
            "review": "Validasi jumlah dan kelengkapan dokumen PO",
            "keterangan": "[Narasi singkat 2–5 kalimat, bernuansa friendly, menjelaskan jumlah PO, kelengkapan, dan insight. Sub-review tidak wajib dipecah dengan semicolon kecuali diperlukan.]"
          }},
          "stage_2_nomor_tanggal": {{
            "review": "Validasi Nomor dan Tanggal Kontrak/SP di semua PO",
            "keterangan": "[Narasi 2–4 kalimat, gunakan semicolon untuk memisahkan sub-review tentang konsistensi internal dan kesesuaian dengan kontrak.]"
          }},
          "stage_3_line_items": {{
            "review": "Validasi DESCRIPTION (month sequence), QUANTITY, dan PRICES di line items",
            "keterangan": "[Narasi 3–5 kalimat, gunakan semicolon untuk memisahkan sub-review: periode, quantity, harga. Bahasa santai dan mudah dimengerti.]"
          }},
          "stage_4_amount_total": {{
            "review": "Validasi PO. AMOUNT, V.A.T, dan TOTAL calculation di semua PO",
            "keterangan": "[Narasi 3–5 kalimat, gunakan semicolon untuk memisahkan sub-review: perhitungan, konsistensi amount, kesesuaian dengan kontrak.]"
          }}
        }}
        
        CRITICAL RULES
        ════════════════════════════════════════════════════════════════════════════
        
        1. Zero Hallucination:
           - Jika data tidak ditemukan, sebutkan sebagai missing di keterangan.
           - Jangan mengarang nilai atau memperkirakan isi dokumen.
        
        2. Zero Tolerance:
           - Semua nilai numerik dan teks penting harus sama persis dengan yang ada di dokumen/kontrak.
           - Tidak ada toleransi pembulatan di PO (berbeda dengan PR).
        
        3. Narasi, Bukan Robot:
           - Gunakan kalimat yang mengalir, seolah menjelaskan ke user awam.
           - Hindari istilah teknis berlebihan, kecuali nama field (Unit Price, Net Price, dll).
        
        4. Semicolon Usage:
           - Gunakan ";" hanya untuk memisahkan sub-review dalam satu keterangan.
           - Jangan gunakan ";" untuk keperluan lain.
        
        5. Bahasa:
           - Narasi dalam Bahasa Indonesia.
           - Nama field teknis tetap menggunakan istilah aslinya dalam Bahasa Inggris jika sudah baku di dokumen.
        
        6. Lanjut Semua Stage:
           - Walaupun di satu stage ditemukan masalah, tetap jalankan review untuk stage lainnya.
           - Setiap stage dinilai dan dijelaskan secara independen.
        
        7. Referensi Kontrak:
           - Saat membandingkan dengan ground truth, selalu sebut sebagai "kontrak", bukan "GT".
        
        RETURN FINAL JSON ANSWER ONLY.
        """,
"GR":
        """
        GRN Review
        ════════════════════════════════════════════════════════════════════════════
        Document Type: GRN (Goods Receipt Notes) - Multiple Documents
        Validation Mode: STRICT DETERMINISTIC (ZERO TOLERANCE)
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output HARUS BERNARASI, SANTAI, TIDAK ROBOT
        - Expected jumlah GRN = jumlah bulan dari start_date sampai end_date (bukan duration)
        - Untuk OTC: expected 1 GRN saja
        - ZERO TOLERANCE: semua nilai harus exact match
        - Tetap lanjutkan ke stage selanjutnya meski ada FAIL di stage sebelumnya
        - Gunakan bahasa Indonesia kecuali untuk field technical (Harga Satuan, Jumlah Harga, dll)
        - Ganti "GT" dengan "kontrak" dalam narasi
        - SEMICOLON (;) HANYA untuk pemisah sub-review, JANGAN gunakan semicolon di tempat lain
        
        ════════════════════════════════════════════════════════════════════════════
        KONTRAK (GROUND TRUTH)
        ════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        
        ════════════════════════════════════════════════════════════════════════════
        GRN DOCUMENTS (Multiple - 1 GRN per page):
        ════════════════════════════════════════════════════════════════════════════
        
        {grn_documents_text}
        
        Format: Setiap halaman = 1 GRN lengkap
        Expected count: Dihitung dari bulan start_date sampai bulan end_date (inklusif)
        
        ════════════════════════════════════════════════════════════════════════════
        TASK: Validate Multiple GRN Documents dengan PRESISI TINGGI
        ════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: VALIDASI NAMA MATERIAL, URUTAN BULAN, DAN JUMLAH GRN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi kelengkapan, urutan bulan, duplicate, dan jumlah GRN
        
        INSTRUKSI:
        
        1. Identify Payment Method (COT in thinking block):
           - Check kontrak.metode_pembayaran
           - If "Bulanan" → generate expected months dari start_date ke end_date
           - If "OTC" → expected count = 1
        
        2. Generate Expected Months List (COT in thinking block):
           ⚠️ CRITICAL LOGIC - BACA DENGAN SEKSAMA:
           
           - If Bulanan:
             * Parse kontrak.jangka_waktu.start_date → extract BULAN & TAHUN saja (abaikan tanggal)
             * Parse kontrak.jangka_waktu.end_date → extract BULAN & TAHUN saja (abaikan tanggal)
             * Generate ALL months dari bulan start sampai bulan end (INKLUSIF)
             * Format kode bulan: JAN, FEB, MAR, APR, MEI, JUN, JUL, AGS, SEP, OKT, NOV, DES
             * Format tahun: 2 digit (23, 24, 25, dll)
             * Format gabungan: [BULAN][TAHUN] (contoh: JAN24, FEB24, AGS23)
             
             CONTOH PERHITUNGAN (PENTING):
             Input: start_date = "09 Agustus 2023", end_date = "08 Agustus 2024"
             Step 1: Extract → start = Agustus 2023, end = Agustus 2024
             Step 2: Generate sequence:
               - Agustus 2023 → AGS23
               - September 2023 → SEP23
               - Oktober 2023 → OKT23
               - November 2023 → NOV23
               - Desember 2023 → DES23
               - Januari 2024 → JAN24
               - Februari 2024 → FEB24
               - Maret 2024 → MAR24
               - April 2024 → APR24
               - Mei 2024 → MEI24
               - Juni 2024 → JUN24
               - Juli 2024 → JUL24
               - Agustus 2024 → AGS24
             Step 3: Expected list = ["AGS23","SEP23","OKT23","NOV23","DES23","JAN24","FEB24","MAR24","APR24","MEI24","JUN24","JUL24","AGS24"]
             Step 4: Expected count = 13 bulan (bukan 12 dari duration!)
             
             ⚠️ JANGAN pakai duration untuk hitung expected count!
             ⚠️ SELALU hitung dari start bulan ke end bulan secara sequential!
             
           - If OTC:
             * Expected list: ["OTC"]
             * Expected count: 1
        
        3. Extract Nama Material dari Setiap GRN:
           - Lokasi: Tabel line item, kolom "Nama Material"
           - Setiap GRN biasanya punya 1 line item
           - Extract full text Nama Material
           - Format umum: [KODE]/[KATEGORI]/[LOKASI]/[BULAN+TAHUN atau OTC]
           - Split by "/" dan ambil segmen terakhir untuk dapat month code
        
        4. Build Actual Months List:
           - Kumpulkan semua month codes dari semua GRN
           - Catat juga nomor GRN untuk setiap month code (untuk tracking duplicate/issue)
           - Format: [(month_code, grn_number), ...]
        
        5. Validate Month Sequence & Count:
           - Compare actual list dengan expected list
           
           Check A: MISSING MONTHS
           - Apakah ada bulan di expected yang tidak ada di actual?
           - Jika ya: list bulan yang missing
           
           Check B: DUPLICATE MONTHS
           - Apakah ada bulan yang muncul lebih dari 1 kali?
           - Jika ya: list bulan duplicate beserta nomor GRN-nya
           
           Check C: EXTRA MONTHS
           - Apakah ada bulan di actual yang tidak ada di expected?
           - CRITICAL: Ini bisa terjadi jika validation logic salah (pakai duration bukan start-end)
           - Jika ya: list bulan extra beserta nomor GRN-nya
           
           Check D: COUNT VALIDATION
           - Total GRN ditemukan vs expected count
           - Expected count = panjang expected list (dari start bulan ke end bulan)
        
        6. Validate Sequence Order:
           - Apakah months di actual list berurutan sequential (sesuai expected order)?
           - Jika tidak sequential: identifikasi gaps atau jumps
        
        7. Sub-review (COT in thinking block):
           - Sub-review 1: Jumlah GRN vs expected (count validation)
           - Sub-review 2: Kelengkapan periode (missing, duplicate, extra months)
           - Sub-review 3: Urutan sequential
           - Pisahkan dengan SEMICOLON (;)
        
        EXPECTED OUTPUT:
        - Review: "Validasi Nama Material, urutan bulan, dan jumlah GRN"
        - Keterangan (naratif, 3–5 kalimat; sub-review pakai semicolon), contoh:
        
          PASS (Bulanan):
            "Ditemukan 13 GRN dengan periode lengkap dan berurutan dari Agustus 2023 hingga Agustus 2024 sesuai jangka waktu kontrak; Tidak ada bulan yang hilang, tidak ada duplicate, dan urutan sudah sequential; Jumlah dokumen sesuai expected (13 bulan dari AGS23 sampai AGS24)."
          
          PASS (OTC):
            "Ditemukan 1 GRN dengan kode OTC di Nama Material sesuai metode pembayaran one-time charging; Dokumen sudah lengkap dan siap untuk validasi harga."
          
          FAIL (Missing):
            "Ditemukan 11 GRN dari expected 13 bulan; Periode yang hilang adalah November 2023 dan Januari 2024 sehingga ada gap di tengah urutan; Dokumen perlu dilengkapi sebelum bisa dinyatakan complete."
          
          FAIL (Duplicate - CRITICAL):
            "Ditemukan 14 GRN padahal expected hanya 13 bulan; Ada duplicate untuk periode Juli 2024 yang muncul di GRN 5000941783 dan GRN 5000942043; Ini critical issue karena satu periode seharusnya hanya punya satu GRN, perlu dipilih mana yang valid."
          
          FAIL (Extra - karena logic validation salah):
            "Ditemukan 13 GRN sesuai expected; Namun ada periode Agustus 2024 (GRN 5000942044) yang sebelumnya dikira 'extra' padahal memang bagian dari kontrak (09 Agustus 2023 - 08 Agustus 2024); Tidak ada missing atau duplicate, semua periode sudah lengkap."
          
          FAIL (Kombinasi - Duplicate + Extra):
            "Ditemukan 14 GRN, melebihi expected 12 bulan; Ada duplicate Juli 2024 di GRN 5000941783 dan GRN 5000942043, plus ada periode Agustus 2024 (GRN 5000942044) yang tidak seharusnya ada jika kontrak hanya 12 bulan; Critical issue: perlu konfirmasi periode kontrak actual dan hapus duplicate."
        
        Tone:
        - Jelas dan tegas untuk duplicate/extra (critical issue)
        - Jelaskan impact dari missing/duplicate/extra
        - Gunakan nomor GRN untuk identifikasi dokumen bermasalah
        - Gunakan SEMICOLON hanya untuk memisahkan sub-review
        
        ─────────────────────────────────────────────────────────────────────────
        
        STAGE 2: VALIDASI HARGA SATUAN, JUMLAH HARGA, DAN TOTAL SEBELUM PPN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi semua field harga di setiap GRN dan konsistensi antar GRN
        
        INSTRUKSI:
        
        1. Determine Expected Price (COT in thinking block):
           - If kontrak.metode_pembayaran = "Bulanan":
             * Expected price = kontrak.harga_satuan
           - If kontrak.metode_pembayaran = "OTC":
             * Expected price = kontrak.dpp
        
        2. Extract dari Setiap GRN:
           - Lokasi: Tabel line item
           - Kolom yang di-extract:
             * Harga Satuan
             * Jumlah Harga
             * Dapat Diterima (atau kolom quantity lain) → untuk dapat Qty
           - Lokasi: Setelah tabel line item
           - Field yang di-extract:
             * Total sebelum PPN
           - Remove formatting (Rp, titik, koma, spasi), convert ke integer
        
        3. Validate Internal Consistency per GRN:
           - Normal case (Qty = 1):
             * Harga Satuan = Jumlah Harga = Total Sebelum PPN
             * Jika tidak sama → FAIL internal consistency
           
           - Rare case (Qty > 1):
             * Jumlah Harga = Harga Satuan × Qty
             * Total Sebelum PPN = Jumlah Harga
             * Flag untuk review: "GRN [nomor] memiliki Qty = [X], perlu ditinjau"
        
        4. Validate Against Kontrak:
           - Harga Satuan = expected price?
           - Total Sebelum PPN = expected price (jika Qty=1)?
           - ZERO TOLERANCE: harus exact match
           - Jika berbeda: hitung selisih dan identifikasi GRN mana
        
        5. Validate Cross-GRN Consistency:
           - Apakah semua GRN punya Harga Satuan yang sama?
           - Apakah semua GRN punya Total Sebelum PPN yang sama?
           - Jika ada yang beda: identifikasi GRN mana dan berapa selisihnya
        
        6. Special Handling - Prorate Pattern Detection (COT):
           - Jika ada 2 GRN dengan harga berbeda (biasanya di awal dan akhir):
             * Tetap flag sebagai "tidak sesuai dengan kontrak"
             * Jangan assume ini prorate kecuali ada bukti eksplisit
             * Jelaskan bahwa ada GRN dengan harga berbeda dari expected
        
        7. Sub-review (COT in thinking block):
           - Sub-review 1: Konsistensi internal per GRN (Harga Satuan = Jumlah = Total)
           - Sub-review 2: Konsistensi harga antar GRN
           - Sub-review 3: Kesesuaian dengan harga di kontrak
           - Pisahkan dengan SEMICOLON (;)
        
        EXPECTED OUTPUT:
        - Review: "Validasi Harga Satuan, Jumlah Harga, dan Total Sebelum PPN di semua GRN"
        - Keterangan (naratif, 3–5 kalimat; sub-review pakai semicolon), contoh:
        
          PASS (All consistent):
            "Semua 13 GRN memiliki Harga Satuan = Jumlah Harga = Total Sebelum PPN = Rp 2.850.000 yang konsisten di setiap dokumen; Nilai ini sesuai dengan harga satuan yang tertera di kontrak; Quantity di semua GRN = 1 sehingga perhitungan internal sudah benar."
          
          PASS (OTC):
            "GRN memiliki Harga Satuan = Jumlah Harga = Total Sebelum PPN = Rp 34.200.000; Nilai ini sesuai dengan DPP di kontrak untuk metode pembayaran OTC; Quantity = 1 dan perhitungan internal konsisten."
          
          FAIL (Internal inconsistent):
            "GRN 5000941775 memiliki error internal: Harga Satuan Rp 2.850.000, Jumlah Harga Rp 2.900.000, Total Sebelum PPN Rp 2.850.000; Seharusnya semua nilai sama karena Qty = 1; GRN ini perlu dikoreksi sebelum bisa dinyatakan valid."
          
          FAIL (Tidak sesuai kontrak):
            "Mayoritas 11 GRN memiliki Harga Satuan = Jumlah Harga = Total Sebelum PPN = Rp 2.850.000 sesuai kontrak; Namun GRN 5000941772 memiliki nilai Rp 2.114.516 (selisih Rp 735.484 dari expected) dan GRN 5000942044 memiliki nilai Rp 735.484 (selisih Rp 2.114.516 dari expected); Kedua GRN ini tidak sesuai dengan harga satuan kontrak dan perlu dikonfirmasi apakah ada alasan khusus atau kesalahan input; Semua GRN punya Qty = 1 sehingga konsistensi internal per dokumen sudah benar."
          
          FAIL (Qty > 1):
            "GRN 5000941780 memiliki Qty = 3 (bukan 1 seperti expected); Perhitungan: Harga Satuan Rp 2.850.000 × 3 = Jumlah Harga Rp 8.550.000 = Total Sebelum PPN; Meskipun calculation benar, case dengan Qty > 1 perlu ditinjau karena biasanya satu GRN mewakili satu bulan dengan Qty = 1; GRN lain sudah konsisten dengan harga kontrak."
        
        Tone:
        - Technical tapi tetap jelas
        - Jelaskan selisih dengan angka konkrit
        - Untuk GRN yang tidak sesuai: tegas tapi tidak menghakimi (bisa jadi ada alasan)
        - Gunakan nomor GRN untuk identifikasi masalah
        - Gunakan SEMICOLON untuk memisahkan aspek validasi yang berbeda
        
        ─────────────────────────────────────────────────────────────────────────
        
        FINAL OUTPUT FORMAT (JSON ONLY)
        ════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_material_sequence_count": {{
            "review": "Validasi Nama Material, urutan bulan, dan jumlah GRN",
            "keterangan": "[Narasi 3–5 kalimat, gunakan semicolon untuk sub-review: count, missing/duplicate/extra, sequence. Dinamis, tegas untuk critical issue.]"
          }},
          
          "stage_2_prices": {{
            "review": "Validasi Harga Satuan, Jumlah Harga, dan Total Sebelum PPN di semua GRN",
            "keterangan": "[Narasi 3–5 kalimat, gunakan semicolon untuk sub-review: internal consistency, cross-GRN consistency, kesesuaian kontrak. Technical tapi jelas.]"
          }}
        }}
        
        CRITICAL RULES
        ════════════════════════════════════════════════════════════════════════════
        
        1. Zero Hallucination:
           - Jika data tidak ditemukan, sebutkan sebagai missing.
           - Jangan mengarang atau memperkirakan nilai.
        
        2. Zero Tolerance:
           - Semua nilai harus exact match dengan kontrak.
           - Harga Satuan, Jumlah Harga, Total Sebelum PPN: exact numeric match.
           - Count: exact number match.
           - NO rounding tolerance.
        
        3. Narasi, Bukan Robot:
           - Gunakan kalimat yang mengalir dan mudah dipahami.
           - Hindari jargon berlebihan kecuali field technical.
        
        4. Semicolon Usage:
           - Gunakan ";" HANYA untuk memisahkan sub-review dalam satu keterangan.
           - Jangan gunakan ";" untuk keperluan lain.
        
        5. Bahasa:
           - Narasi dalam Bahasa Indonesia.
           - Field technical tetap menggunakan istilah asli (Harga Satuan, Jumlah Harga, Total Sebelum PPN, Nama Material, dll).
        
        6. Lanjut Semua Stage:
           - Walaupun Stage 1 FAIL, tetap jalankan Stage 2.
           - Setiap stage dinilai independen.
        
        7. Referensi Kontrak:
           - Selalu sebut "kontrak", bukan "GT".
           - Contoh: "sesuai dengan harga satuan kontrak".
        
        8. Month Calculation Logic (PENTING):
           - JANGAN pakai duration untuk expected count.
           - SELALU hitung dari bulan start_date sampai bulan end_date (inklusif).
           - Format: Ambil BULAN + TAHUN saja, abaikan tanggal.
           - Generate ALL months dalam range tersebut secara sequential.
        
        9. Duplicate & Extra Handling:
           - Duplicate: CRITICAL ISSUE, sebutkan nomor GRN yang duplicate.
           - Extra: Bisa jadi validation logic salah (cek apakah expected list sudah benar).
           - Tone: Tegas dan jelas untuk critical issue.
        
        10. GRN Identification:
            - Gunakan nomor GRN untuk identifikasi dokumen bermasalah.
            - Format: "GRN [nomor]" (contoh: GRN 5000941783).
        
        11. Qty Handling:
            - Normal: Qty = 1.
            - Jika Qty > 1: flag untuk review.
            - Validate calculation: Jumlah Harga = Harga Satuan × Qty.
        
        12. Prorate Detection:
            - JANGAN assume prorate.
            - Tetap flag sebagai "tidak sesuai dengan kontrak".
            - Jelaskan selisih dengan angka konkrit.
        
        13. Dynamic Output:
            - Adjust narasi sesuai kondisi yang ditemukan.
            - Jika ada anomali: jelaskan dengan insight meaningful.
            - Jika semua OK: berikan confidence.
        
        14. COT in Thinking Block Only:
            - Semua reasoning ada di thinking block.
            - Output hanya hasil akhir dan insight.
        
        15. Numeric Format:
            - Gunakan titik untuk thousand separator: Rp 2.850.000.
            - Gunakan "Rp" prefix.
            - Sebutkan nilai konkrit.
        
        RETURN FINAL JSON ANSWER ONLY - NO COT EXPLANATION IN OUTPUT.
        """,
"SPB":
        """
        SPB vs GROUND TRUTH VALIDATION PROMPT - ENHANCED VERSION
        ═════════════════════════════════════════════════════════════════════════════
        Document Type: SPB (Surat Penagihan/Surat Permohonan Pembayaran)
        Validation Mode: STRICT DETERMINISTIC (ZERO TOLERANCE)
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - JANGAN INFER atau ASSUME
        - Jika tidak ditemukan dengan PASTI → catat sebagai error/missing dalam keterangan
        - EXACT MATCH untuk semua critical fields
        - Pay SPECIAL ATTENTION to PPN calculation (11%)
        - Gunakan Chain of Thought (COT) dalam thinking block, tapi output hanya insight saja
        - Keterangan harus SINGKAT dan JELAS: jika benar bilang benar, jika salah jelaskan kenapa
        
        ═════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ═════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        ═════════════════════════════════════════════════════════════════════════════
        SPB DOCUMENT (to validate):
        ═════════════════════════════════════════════════════════════════════════════
        
        {spb_document_text}
        
        ═════════════════════════════════════════════════════════════════════════════
        TASK: Validate SPB Document dengan PRESISI TINGGI
        ═════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: JUDUL PROJECT & NOMOR REFERENSI KONTRAK VALIDATION
        ─────────────────────────────────────────────────────────────────────────
        
        INSTRUKSI:
        1. Cari judul/deskripsi project di dokumen SPB
           - Lokasi umum: setelah "Kontrak Layanan", "Menunjuk Kontrak Layanan", atau di "Perihal"
           - Format umum: "Kontrak Layanan [JUDUL_PROJECT]" atau "Perihal : [JUDUL_PROJECT]"
           - Extract full text dari judul (bisa multi-line)
        
        2. Cari SEMUA kemunculan nomor kontrak/referensi di SPB
           - Pattern: "Kontrak Layanan No :", "No :", "Nomor :", "Nota Pesanan No.", "Surat Pesanan No:"
           - Format umum: [PREFIX].[NUMBER]/[CODE]/[YEAR]
           - Contoh: "K.TEL.0050/HK.810/TR6-R600/2024"
           - Extract SEMUA nomor yang ditemukan di dokumen
        
        3. VALIDATION:
           A. Judul Project:
              - Normalize: lowercase, trim whitespace
              - Compare dengan GT.judul_project
              - Case-insensitive, minor format variation OK
              - Content/makna harus sama
           
           B. Nomor Referensi Kontrak:
              - Check: apakah GT.nomor_surat_utama ADA di SPB?
              - Check: apakah GT.nomor_surat_lainnya ADA di SPB (jika tidak null)?
              - Minimal SATU nomor dari GT harus ditemukan di SPB
              - Exact match required (case-sensitive, character-by-character)
        
        EXPECTED OUTPUT:
        - Review: "Validasi judul project dan nomor referensi kontrak di SPB"
        - Keterangan: 
          * PASS: "Sudah benar. Judul project '[judul]' dan nomor kontrak '[nomor]' match dengan Ground Truth."
          * FAIL (judul): "Ada yang salah. Judul project berbeda: SPB '[judul_spb]' vs GT '[judul_gt]'."
          * FAIL (nomor): "Ada yang salah. Nomor kontrak tidak ditemukan. GT memiliki '[nomor_gt]' tapi tidak ada di SPB."
          * FAIL (both): "Ada yang salah. Judul project dan nomor kontrak tidak match."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 2: PERIODE VALIDATION (DYNAMIC CALCULATION)
        ─────────────────────────────────────────────────────────────────────────
        
        INSTRUKSI:
        1. Cari periode di dokumen SPB
           - Lokasi umum: setelah judul project atau nomor kontrak
           - Format umum: "Periode [Start] s.d [End]" atau "Periode [Start] sd [End]" atau "bulan [Bulan] [Tahun]"
           - Extract periode yang disebutkan
        
        2. Normalize format periode:
           - Jika ada tanggal: "DD Bulan YYYY s.d DD Bulan YYYY"
           - Jika hanya bulan: "Bulan YYYY s.d Bulan YYYY"
           - Jika single month: "Bulan YYYY"
           - Standardize separator: " s.d " atau " sd " → " s.d "
        
        3. Parse komponen periode SPB:
           - Start: month/year atau full date
           - End: month/year atau full date (jika disebutkan)
           - Durasi (dalam bulan): hitung dari start ke end
        
        4. Compare dengan GT.jangka_waktu:
           - Jika SPB ada full date → compare dengan GT.jangka_waktu.start_date dan end_date
           - Jika SPB hanya bulan/tahun → compare bulan dan tahun saja
           - Check: apakah periode SPB WITHIN RANGE jangka waktu GT?
        
        EXPECTED OUTPUT:
        - Review: "Validasi periode kontrak di SPB"
        - Keterangan:
          * PASS: "Sudah benar. Periode '[periode_spb]' match dengan jangka waktu Ground Truth."
          * PASS (partial): "Sudah benar. Periode '[periode_spb]' berada dalam range jangka waktu GT (format berbeda tapi valid)."
          * FAIL: "Ada yang salah. Periode SPB '[periode_spb]' tidak dalam range GT '[start_gt] s.d [end_gt]'."
          * FAIL (not found): "Ada yang salah. Periode tidak ditemukan di SPB."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 3: NOMOR KONTRAK VALIDATION (ALL OCCURRENCES)
        ─────────────────────────────────────────────────────────────────────────
        
        INSTRUKSI:
        1. Cari SEMUA kemunculan nomor kontrak di SPB (sudah dilakukan di Stage 1)
        2. Extract setiap nomor dengan format: [PREFIX].[NUMBER]/[CODE]/[YEAR]
        3. Preserve ALL characters exactly (dots, slashes, hyphens)
        4. Compare SETIAP nomor yang ditemukan dengan:
           - GT.nomor_surat_utama
           - GT.nomor_surat_lainnya (jika tidak null)
        
        5. VALIDATION:
           - Minimal SATU nomor dari GT harus ada di SPB
           - SEMUA nomor di SPB harus match dengan salah satu nomor di GT
           - Jika ada nomor di SPB yang TIDAK ADA di GT → FLAG sebagai error
           - Exact match required (case-sensitive, character-by-character)
        
        EXPECTED OUTPUT:
        - Review: "Validasi semua kemunculan nomor kontrak di SPB"
        - Keterangan:
          * PASS: "Sudah benar. Nomor kontrak '[nomor]' ditemukan dan match dengan Ground Truth."
          * PASS (multiple): "Sudah benar. Semua nomor kontrak di SPB ([nomor1], [nomor2]) match dengan GT."
          * FAIL (not found): "Ada yang salah. Nomor kontrak GT '[nomor_gt]' tidak ditemukan di SPB."
          * FAIL (wrong number): "Ada yang salah. SPB menyebutkan nomor '[nomor_spb]' yang tidak ada di GT."
          * FAIL (mismatch): "Ada yang salah. Nomor di SPB '[nomor_spb]' berbeda dari GT '[nomor_gt]' pada karakter ke-[posisi]."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 4: TANGGAL KONTRAK VALIDATION
        ─────────────────────────────────────────────────────────────────────────
        
        INSTRUKSI:
        1. Cari tanggal kontrak di SPB
           - Lokasi umum: setelah nomor kontrak
           - Format: "Tanggal :", "tanggal", "Tgl :"
           - Extract: "DD Bulan YYYY"
        
        2. Normalize format:
           - Ensure DD dengan leading zero (01, 02, ..., 31)
           - Ensure month name full (Januari, Februari, ..., Desember)
           - Ensure year 4 digits (2024, 2025, etc.)
        
        3. Convert ke format DD-MM-YYYY untuk comparison dengan GT
        
        4. Compare dengan GT.tanggal_kontrak (format: "DD-MM-YYYY")
        
        VALIDATION:
        - Exact match required (day, month, year)
        
        EXPECTED OUTPUT:
        - Review: "Validasi tanggal kontrak di SPB"
        - Keterangan:
          * PASS: "Sudah benar. Tanggal kontrak '[tanggal]' match dengan Ground Truth."
          * FAIL: "Ada yang salah. Tanggal di SPB '[tanggal_spb]' berbeda dari GT '[tanggal_gt]'."
          * FAIL (not found): "Ada yang salah. Tanggal kontrak tidak ditemukan di SPB."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 5: TOTAL PEMBAYARAN VALIDATION (DYNAMIC PPN CALCULATION)
        ─────────────────────────────────────────────────────────────────────────
        
        ⚠️ CRITICAL: PPN HANDLING & DYNAMIC CALCULATION
        
        INSTRUKSI:
        
        STEP 1: Extract Total Pembayaran dari SPB
        1. Cari total pembayaran di SPB
           - Pattern: "sebesar Rp.", "Total Rp.", "Nilai Tagihan Rp."
           - Extract numeric amount (remove "Rp", dots, commas, spaces)
        
        2. Identify PPN indicator:
           - "sudah termasuk PPN" → amount is AFTER PPN
           - "termasuk PPN" → amount is AFTER PPN
           - "inclusive PPN" → amount is AFTER PPN
           - "belum termasuk PPN" → amount is BEFORE PPN (rare case)
           - No indicator → assume AFTER PPN (default for SPB)
        
        STEP 2: Extract Periode dari SPB (dari Stage 2)
        - Periode SPB format: "[Start] s.d [End]"
        - Calculate durasi dalam bulan
        
        STEP 3: Determine Expected Amount Based on Periode
        
        A. Jika Periode SPB = Full Jangka Waktu GT:
           - Periode SPB.start_date = GT.jangka_waktu.start_date
           - Periode SPB.end_date = GT.jangka_waktu.end_date
           - Expected calculation:
             * Total sebelum PPN = GT.dpp
             * Total setelah PPN = GT.dpp × 1.11
        
        B. Jika Periode SPB = Partial (lebih pendek dari jangka waktu GT):
           - Calculate durasi SPB (dalam bulan) dari periode yang disebutkan
           - Expected calculation:
             * Total sebelum PPN = GT.harga_satuan × durasi_bulan
             * Total setelah PPN = (GT.harga_satuan × durasi_bulan) × 1.11
        
        C. Jika Periode SPB = Single Month:
           - Durasi = 1 bulan
           - Expected calculation:
             * Total sebelum PPN = GT.harga_satuan
             * Total setelah PPN = GT.harga_satuan × 1.11
        
        STEP 4: Compare SPB Amount dengan Expected Amount
        1. Parse SPB amount (convert to integer)
        2. Parse expected amount (convert to integer)
        3. Compare: EXACT match required (no tolerance)
        4. If mismatch: calculate difference
        
        CALCULATION FORMULA:
        Parse GT values
        dpp_numeric = parse_amount(GT.dpp)
        harga_satuan_numeric = parse_amount(GT.harga_satuan)
        Determine periode duration
        if periode_spb == full_jangka_waktu:
        total_sebelum_ppn = dpp_numeric
        else:
        durasi_bulan = calculate_duration(periode_spb.start, periode_spb.end)
        total_sebelum_ppn = harga_satuan_numeric × durasi_bulan
        Calculate with PPN
        total_setelah_ppn = total_sebelum_ppn × 1.11
        Compare
        if spb_ppn_indicator == "sudah termasuk PPN":
        expected = total_setelah_ppn
        else:
        expected = total_sebelum_ppn
        match = (spb_amount == expected)
        
        VALIDATION:
        - Exact numeric match required
        - If mismatch: calculate difference and percentage
        
        EXPECTED OUTPUT:
        - Review: "Validasi total pembayaran di SPB (dengan perhitungan PPN dan durasi periode)"
        - Keterangan:
          * PASS: "Sudah benar. Total pembayaran Rp [nilai] (sudah termasuk PPN) match dengan perhitungan: [base] × [durasi] × 1.11 = Rp [expected]."
          * PASS (full period): "Sudah benar. Total pembayaran Rp [nilai] (sudah termasuk PPN) match dengan DPP × 1.11 = Rp [expected]."
          * FAIL (amount): "Ada yang salah. Total di SPB Rp [nilai_spb] tidak match dengan expected Rp [nilai_expected] (base: Rp [base], durasi: [durasi] bulan, dengan PPN 11%). Selisih: Rp [difference]."
          * FAIL (unclear PPN): "Ada yang salah. Indikator PPN tidak jelas di SPB, tidak bisa memastikan apakah Rp [nilai] sudah termasuk PPN atau belum."
          * FAIL (not found): "Ada yang salah. Total pembayaran tidak ditemukan di SPB."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 6: DETAIL REKENING VALIDATION (ALL 4 FIELDS)
        ─────────────────────────────────────────────────────────────────────────
        
        INSTRUKSI:
        1. Cari section banking/rekening di SPB
           - Lokasi umum: setelah total pembayaran
           - Pattern: "Pembayaran mohon ditransfer...", "mohon ditransfer ke rekening..."
        
        2. Extract 4 fields:
        
           A. NAMA BANK:
           - Pattern: "Bank :", "Nama Bank :", "melalui Bank", "ke Bank"
           - Extract bank name only (tanpa "Bank" prefix)
           - Examples: "Mandiri", "BCA", "BNI", "BRI"
           - Normalize: remove "Bank" prefix, trim whitespace
           - Compare dengan: GT.detail_rekening.nama_bank
           - Case-insensitive
        
           B. KANTOR CABANG:
           - Pattern: "Cabang :", "Cab.", "Kantor Cabang :", "Cab.KK"
           - Extract full branch name
           - Examples: "Jakarta Cut Meutia", "KK Balikpapan Telkom Divre VI", "Banjarmasin"
           - Compare dengan: GT.detail_rekening.kantor_cabang
           - Case-insensitive, minor spelling variation OK
        
           C. NOMOR REKENING:
           - Pattern: "A/C :", "No. Rek :", "Rekening :", "rekening Nomor:", "NO ACC :"
           - Extract account number
           - Format variations: "123-0097021697", "149-000700-5806", "031.000.642.1179"
           - Normalize: keep dashes and dots, remove spaces
           - Compare dengan: GT.detail_rekening.nomor_rekening
           - EXACT format match required
        
           D. ATAS NAMA:
           - Pattern: "Atas Nama :", "A/N :", "a/n", "An."
           - Extract complete account holder name
           - May include "PT.", "Koperasi", "CV", etc.
           - Compare dengan: GT.detail_rekening.atas_nama
           - Case-insensitive, minor format variation OK
        
        3. AGGREGATE VALIDATION:
           - ALL 4 fields must match untuk PASS
           - Track which fields pass and which fail
           - If multiple failures: list all
        
        VALIDATION:
        - Bank name: case-insensitive match
        - Cabang: case-insensitive, fuzzy match OK (≥80% similarity)
        - Nomor rekening: EXACT format match
        - Atas nama: case-insensitive, fuzzy match OK (≥90% similarity)
        
        EXPECTED OUTPUT:
        - Review: "Validasi detail rekening (bank, cabang, nomor rekening, atas nama) di SPB"
        - Keterangan:
          * PASS: "Sudah benar. Semua detail rekening cocok: Bank [bank], Cabang [cabang], Rekening [rekening], Atas Nama [nama]."
          * FAIL (single): "Ada yang salah. [Field] tidak match: SPB '[nilai_spb]' vs GT '[nilai_gt]'."
          * FAIL (multiple): "Ada yang salah. [jumlah] field tidak match: (1) [field1]: '[spb1]' vs '[gt1]', (2) [field2]: '[spb2]' vs '[gt2]'."
          * FAIL (not found): "Ada yang salah. Detail rekening tidak ditemukan atau tidak lengkap di SPB."
        
        ═════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT JSON (STRICT FORMAT - NO DEVIATIONS)
        ═════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_judul_project": {{
            "review": "Validasi judul project dan nomor referensi kontrak di SPB",
            "keterangan": "[Singkat dan jelas: jika benar bilang benar dengan nilai, jika salah jelaskan kenapa]"
          }},
          
          "stage_2_periode": {{
            "review": "Validasi periode kontrak di SPB",
            "keterangan": "[Singkat dan jelas: jika benar bilang benar dengan nilai, jika salah jelaskan kenapa]"
          }},
          
          "stage_3_nomor_kontrak": {{
            "review": "Validasi semua kemunkulan nomor kontrak di SPB",
            "keterangan": "[Singkat dan jelas: jika benar bilang benar dengan nilai, jika salah jelaskan kenapa]"
          }},
          
          "stage_4_tanggal_kontrak": {{
            "review": "Validasi tanggal kontrak di SPB",
            "keterangan": "[Singkat dan jelas: jika benar bilang benar dengan nilai, jika salah jelaskan kenapa]"
          }},
          
          "stage_5_total_pembayaran": {{
            "review": "Validasi total pembayaran di SPB (dengan perhitungan PPN dan durasi periode)",
            "keterangan": "[Singkat dan jelas: jika benar bilang benar dengan nilai dan perhitungan, jika salah jelaskan kenapa dengan detail calculation]"
          }},
          
          "stage_6_detail_rekening": {{
            "review": "Validasi detail rekening (bank, cabang, nomor rekening, atas nama) di SPB",
            "keterangan": "[Singkat dan jelas: jika benar bilang benar dengan semua nilai, jika salah jelaskan field mana dan kenapa]"
          }}
        }}
        
        ═════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES - NO EXCEPTIONS
        ═════════════════════════════════════════════════════════════════════════════
        
        1. ZERO HALLUCINATION:
           ✅ Jika tidak ditemukan → catat sebagai error dalam keterangan
           ✅ Jangan guess atau predict nilai
        
        2. EXACT MATCH:
           ✅ Nomor kontrak: character-by-character
           ✅ Nomor rekening: character-by-character
           ✅ Total pembayaran: numeric exact match
           ✅ Tanggal: exact date match
        
        3. PPN CALCULATION (11%):
           ✅ Always identify PPN indicator di SPB
           ✅ Default assumption: "sudah termasuk PPN" if not stated
           ✅ Formula: Total setelah PPN = Total sebelum PPN × 1.11
        
        4. DYNAMIC PERIODE HANDLING:
           ✅ Full period → use GT.dpp
           ✅ Partial period → use GT.harga_satuan × durasi_bulan
           ✅ Single month → use GT.harga_satuan
           ✅ Always calculate PPN on top
        
        5. OUTPUT FORMAT:
           ✅ ONLY JSON output (no free text)
           ✅ Keterangan: SINGKAT dan JELAS
           ✅ Jika benar: bilang benar dengan nilai konkrit
           ✅ Jika salah: jelaskan kenapa dengan singkat (tanpa COT detail)
        
        6. CHAIN OF THOUGHT:
           ✅ Use COT dalam thinking block (internal reasoning)
           ✅ Output hanya insight/hasil akhir
           ✅ Keterangan fokus pada: apa yang salah, kenapa salah, nilai apa
        
        RETURN FINAL JSON ANSWER ONLY.
        """,

"KUITANSI":
        """
        KUITANSI vs GROUND TRUTH VALIDATION PROMPT - OPTIMIZED VERSION
        ═════════════════════════════════════════════════════════════════════════════
        Document Type: KUITANSI (Receipt/Payment Receipt)
        Validation Mode: STRICT DETERMINISTIC (ZERO TOLERANCE)
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output harus SINGKAT, JELAS, dan BERSAHABAT
        - ZERO TOLERANCE: semua nilai harus exact match
        - Handle full period dan partial period dengan dynamic calculation
        
        ═════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ═════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
    
        
        ═════════════════════════════════════════════════════════════════════════════
        KUITANSI DOCUMENT (Input - May contain duplicate pages):
        ═════════════════════════════════════════════════════════════════════════════
        
        {kuitansi_document_text}
        
        Format: Multi-page possible, halaman 2+ biasanya duplicate dari halaman 1
        Action: Extract from first occurrence, ignore duplicates
        
        ═════════════════════════════════════════════════════════════════════════════
        TASK: Validate KUITANSI Document dengan PRESISI TINGGI
        ═════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: NOMOR KONTRAK REFERENCE VALIDATION
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi bahwa Nomor Kontrak/SP/NOPES di Kwitansi match dengan GT
        
        INSTRUKSI:
        
        1. **Extract Nomor Kontrak Reference:**
           - Cari field reference di bagian bawah/footer Kwitansi
           - Possible labels:
             * "No. Nota Pesanan :"
             * "No. SP :"
             * "No. NOPES :"
             * "No. Kontrak :"
           - Extract nilai exact (preserve dots, slashes, spaces)
           - Format umum: [PREFIX].[NUMBER]/[CODE]/[YEAR]
           - If not found → flag as missing
        
        2. **Normalize for Comparison:**
           - Remove extra spaces
           - Keep dots, slashes, and dashes
           - Case-insensitive comparison
        
        3. **Validate Against Ground Truth:**
           - Compare dengan GT.nomor_surat_utama (primary)
           - IF NOT match → compare dengan GT.nomor_surat_lainnya (secondary)
           - IF both NOT match → FAIL
           - Character-by-character comparison
        
        EXPECTED OUTPUT:
        - Review: "Validasi Nomor Kontrak reference di Kwitansi"
        - Keterangan:
          * **PASS**: "Sudah benar. Nomor Kontrak '[nomor]' di Kwitansi match dengan Ground Truth."
          * **FAIL**: "Ada yang salah. Nomor Kontrak di Kwitansi '[nomor_kwitansi]' tidak match dengan Ground Truth '[nomor_gt]'."
          * **FAIL (not found)**: "Ada yang salah. Nomor Kontrak reference tidak ditemukan di Kwitansi."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 2: PERIODE VALIDATION
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi periode pembayaran di Kwitansi vs GT.jangka_waktu
        
        INSTRUKSI:
        
        1. **Extract Periode from Kwitansi:**
           - Lokasi: bagian "Untuk pembayaran :" atau "Buat pembayaran :"
           - Look for pattern: "Periode [BULAN] [TAHUN] s.d [BULAN] [TAHUN]"
           - OR: "[BULAN] [TAHUN] sd [BULAN] [TAHUN]"
           - OR: "PERIODE [BULAN] [TAHUN] S/D [BULAN] [TAHUN]"
           - Extract start month + year and end month + year
           - Parse ke format: start_month, start_year, end_month, end_year
        
        2. **Calculate Coverage (COT in thinking block):**
           - Count jumlah bulan dari start ke end
           - Handle year transition (e.g., Nov 2024 - Feb 2025 = 4 bulan)
           - Store as: kwitansi_periode_bulan (integer)
        
        3. **Extract Expected from Ground Truth:**
           - Parse GT.jangka_waktu.start_date → extract month + year
           - Parse GT.jangka_waktu.end_date → extract month + year
           - Parse GT.jangka_waktu.duration → extract number (e.g., "12 Bulan" → 12)
        
        4. **Validate Period Match:**
           - Compare start month + year (Kwitansi vs GT)
           - Compare end month + year (Kwitansi vs GT)
           - Compare calculated duration (Kwitansi vs GT)
           - Note: Cukup match bulan + tahun (ignore tanggal spesifik)
        
        5. **Determine Coverage Type:**
           - IF kwitansi_periode_bulan == GT.duration → FULL PERIOD
           - IF kwitansi_periode_bulan < GT.duration → PARTIAL PERIOD
           - Store coverage_type for use in Stage 3
        
        EXPECTED OUTPUT:
        - Review: "Validasi periode pembayaran di Kwitansi"
        - Keterangan:
          * **PASS (full)**: "Sudah benar. Periode di Kwitansi '[start] s.d [end]' ([X] bulan) match dengan jangka waktu kontrak (full period coverage)."
          * **PASS (partial)**: "Sudah benar. Periode di Kwitansi '[start] s.d [end]' ([X] bulan) adalah partial coverage dari jangka waktu kontrak ([Y] bulan total)."
          * **FAIL (start)**: "Ada yang salah. Start periode di Kwitansi '[start_kwi]' tidak match dengan kontrak '[start_gt]'."
          * **FAIL (end)**: "Ada yang salah. End periode di Kwitansi '[end_kwi]' tidak match dengan expected '[end_gt]' untuk coverage [X] bulan."
          * **FAIL (not found)**: "Ada yang salah. Periode tidak ditemukan di Kwitansi."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 3: PPN INDICATOR CHECK
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Tentukan apakah nilai di Kwitansi sudah include PPN atau belum
        
        INSTRUKSI:
        
        1. **Search for PPN Indicator:**
           - Look for keywords (case-insensitive):
             * "SUDAH TERMASUK PPN"
             * "TAGIHAN SUDAH TERMASUK PPN"
             * "Sudah Termasuk Pajak"
             * "Termasuk PPN"
             * "Include PPN"
           - Location: biasanya di bawah periode atau setelah jumlah
           - If found → kwitansi_includes_ppn = TRUE
           - If NOT found → kwitansi_includes_ppn = FALSE (assume TRUE with WARNING)
        
        2. **Flag Status:**
           - IF indicator found → Status: CLEAR (ada indikator jelas)
           - IF indicator NOT found → Status: WARNING (tidak ada indikator, assume includes PPN)
        
        EXPECTED OUTPUT:
        - Review: "Check indikator PPN di Kwitansi"
        - Keterangan:
          * **PASS**: "Sudah benar. Ditemukan indikator '[text_indikator]' - nilai Kwitansi sudah include PPN."
          * **WARNING**: "Perlu ditinjau. Tidak ditemukan indikator PPN yang jelas di Kwitansi. Asumsi: nilai sudah include PPN."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 4: TOTAL AMOUNT VALIDATION (WITH DYNAMIC CALCULATION)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi total pembayaran (DPP + PPN) di Kwitansi vs GT
        
        INSTRUKSI:
        
        1. **Extract Total from Kwitansi:**
           - Lokasi: Field "JUMLAH" atau "Rp." dengan nilai numeric terbesar
           - Common patterns:
             * "JUMLAH Rp. [AMOUNT]"
             * "Rp [AMOUNT]"
             * "Rp. [AMOUNT]"
           - Remove formatting: dots, commas, spaces
           - Convert to integer
           - Store as: kwitansi_total (integer)
        
        2. **Determine Expected Value (COT in thinking block):**
        
           **Use results from Stage 2 & 3:**
           - coverage_type: FULL or PARTIAL
           - kwitansi_periode_bulan: integer (jumlah bulan)
           - kwitansi_includes_ppn: boolean
        
           **Logic:**
        
           **IF coverage_type == FULL PERIOD:**
           - IF kwitansi_includes_ppn == TRUE:
             * Expected = GT.dpp_plus_ppn
           - IF kwitansi_includes_ppn == FALSE:
             * Expected = GT.dpp_raw
        
           **IF coverage_type == PARTIAL PERIOD:**
           - IF kwitansi_includes_ppn == TRUE:
             * Expected = GT.harga_satuan_plus_ppn × kwitansi_periode_bulan
           - IF kwitansi_includes_ppn == FALSE:
             * Expected = GT.harga_satuan_raw × kwitansi_periode_bulan
        
        3. **Validate Exact Match:**
           - Compare: kwitansi_total vs Expected
           - Calculate difference: abs(kwitansi_total - Expected)
           - Calculate percentage: (difference / Expected) × 100
           - IF difference == 0 → PASS
           - IF difference > 0 → FAIL dengan detail
        
        4. **Provide Context in Output:**
           - Mention: coverage type (full/partial)
           - Mention: PPN status (include/exclude)
           - Mention: calculation method used
        
        EXPECTED OUTPUT:
        - Review: "Validasi total pembayaran (DPP + PPN) di Kwitansi"
        - Keterangan:
          * **PASS (full, with PPN)**: "Sudah benar. Total Kwitansi Rp [nilai] match dengan GT.dpp_plus_ppn (full period coverage, sudah include PPN)."
          * **PASS (full, no PPN)**: "Sudah benar. Total Kwitansi Rp [nilai] match dengan GT.dpp_raw (full period coverage, belum include PPN)."
          * **PASS (partial, with PPN)**: "Sudah benar. Total Kwitansi Rp [nilai] match dengan perhitungan: GT.harga_satuan_plus_ppn × [X] bulan = Rp [expected] (partial coverage, sudah include PPN)."
          * **PASS (partial, no PPN)**: "Sudah benar. Total Kwitansi Rp [nilai] match dengan perhitungan: GT.harga_satuan_raw × [X] bulan = Rp [expected] (partial coverage, belum include PPN)."
          * **FAIL**: "Ada yang salah. Total Kwitansi Rp [nilai_kwi] tidak match dengan expected Rp [nilai_expected] ([coverage_type], [ppn_status]). Selisih: Rp [difference] ([percentage]%)."
          * **FAIL (not found)**: "Ada yang salah. Total amount tidak ditemukan di Kwitansi."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 5: TERBILANG CONSISTENCY CHECK (INTERNAL)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi consistency antara terbilang (text) dan jumlah (numeric)
        
        INSTRUKSI:
        
        1. **Extract Terbilang:**
           - Lokasi: Field "Banyaknya uang :" atau "Jumlah uang :" atau "Terbilang :"
           - Extract full text in Indonesian
           - Common patterns:
             * "Dua ratus tiga puluh tujuh juta enam ratus dua puluh ribu seratus empat puluh dua Rupiah"
             * "# Sembilan puluh sembilan juta ... #"
           - Remove "#" markers and "Rupiah" suffix
           - Store as: terbilang_text (string)
        
        2. **Parse Terbilang to Numeric (Heuristic):**
           - Use Indonesian number word mapping:
             * satu=1, dua=2, tiga=3, ..., sembilan=9
             * sepuluh=10, seratus=100, seribu=1000
             * puluh=×10, ratus=×100, ribu=×1000, juta=×1000000
           - Parse structure: [ratus juta] + [puluh juta] + [juta] + [ratus ribu] + ...
           - Handle "se-" prefix (seratus = 100, seribu = 1000, sejuta = 1000000)
           - Convert to integer: terbilang_numeric
        
        3. **Compare with Kwitansi Total:**
           - Get kwitansi_total from Stage 4
           - Compare: terbilang_numeric vs kwitansi_total
           - IF match → consistent
           - IF NOT match → inconsistent (flag)
        
        4. **Handle Parse Failure:**
           - IF parsing terbilang fails (complex format, OCR errors):
             * Return: "Tidak dapat parse terbilang untuk validasi otomatis"
             * Status: SKIP (not FAIL)
        
        EXPECTED OUTPUT:
        - Review: "Validasi consistency terbilang dengan jumlah numeric di Kwitansi"
        - Keterangan:
          * **PASS**: "Sudah benar. Terbilang '[text_short]...' consistent dengan jumlah numeric Rp [nilai]."
          * **FAIL**: "Ada yang salah. Terbilang '[text_short]...' (parsed: Rp [parsed]) tidak consistent dengan jumlah numeric Rp [nilai_kwi]. Selisih: Rp [difference]."
          * **SKIP**: "Tidak dapat parse terbilang untuk validasi otomatis. Manual review recommended."
        
        ─────────────────────────────────────────────────────────────────────────
        NOTES:
        ─────────────────────────────────────────────────────────────────────────
        
        **COVERAGE TYPE DETERMINATION:**
        - FULL PERIOD: kwitansi_periode_bulan == GT.duration
        - PARTIAL PERIOD: kwitansi_periode_bulan < GT.duration
        
        **PPN HANDLING:**
        - Always look for explicit indicator
        - If not found: assume includes PPN with WARNING
        - Never assume excludes PPN (safety default)
        
        **TERBILANG PARSING (Indonesian Number Words):**
        Map words to numbers:
        - Units: satu=1, dua=2, tiga=3, empat=4, lima=5, enam=6, tujuh=7, delapan=8, sembilan=9
        - Tens: sepuluh/se puluh=10, dua puluh=20, tiga puluh=30, ..., sembilan puluh=90
        - Hundreds: seratus/se ratus=100, dua ratus=200, ..., sembilan ratus=900
        - Thousands: seribu/se ribu=1000, dua ribu=2000, ..., sembilan ribu=9000
        - Multipliers: ribu=×1000, juta=×1000000, miliar=×1000000000
        
        **DUPLICATE PAGE HANDLING:**
        - Extract from first occurrence only
        - Ignore duplicate pages (halaman 2+)
        - If halaman 1 & 2 have different values → flag inconsistency
        
        ═════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT FORMAT (JSON ONLY - KEEP IT CONCISE)
        ═════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_nomor_kontrak": {{
            "review": "Validasi Nomor Kontrak reference di Kwitansi",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah dengan detail minimal]"
          }},
          
          "stage_2_periode": {{
            "review": "Validasi periode pembayaran di Kwitansi",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah dengan detail minimal]"
          }},
          
          "stage_3_ppn_indicator": {{
            "review": "Check indikator PPN di Kwitansi",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah/warning dengan detail minimal]"
          }},
          
          "stage_4_total_amount": {{
            "review": "Validasi total pembayaran (DPP + PPN) di Kwitansi",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah dengan detail minimal. Mention coverage type dan PPN status]"
          }},
          
          "stage_5_terbilang": {{
            "review": "Validasi consistency terbilang dengan jumlah numeric di Kwitansi",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah/skip dengan detail minimal]"
          }}
        }}
        
        ═════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES
        ═════════════════════════════════════════════════════════════════════════════
        
        1. **ZERO HALLUCINATION:**
           - Jika tidak ditemukan → catat sebagai missing
           - Jangan guess atau assume (except PPN default with WARNING)
        
        2. **ZERO TOLERANCE:**
           - Semua nilai harus exact match (no rounding)
           - Nomor Kontrak: character-by-character
           - Periode: month + year match
           - Total: exact numeric match
        
        3. **CONCISE OUTPUT:**
           - Keterangan maksimal 2-3 kalimat
           - Fokus pada insight, bukan process
           - Jika benar: bilang benar dengan nilai konkrit dan context
           - Jika salah: jelaskan apa yang salah dengan detail
        
        4. **FRIENDLY TONE:**
           - "Sudah benar" bukan "Valid"
           - "Ada yang salah" bukan "Invalid"
           - "Perlu ditinjau" untuk edge cases/warnings
        
        5. **COT IN THINKING BLOCK:**
           - Semua reasoning ada di thinking block
           - Output hanya hasil akhir dan insight
           - Tidak ada COT detail di keterangan
        
        6. **DYNAMIC CALCULATION:**
           - Expected value depends on: coverage type + PPN status
           - Always mention calculation method in keterangan (for transparency)
           - Formula: GT.field × jumlah_bulan (for partial)
        
        7. **PPN INDICATOR PRIORITY:**
           - Always check for explicit indicator first
           - Default to "includes PPN" with WARNING if not found
           - Never assume "excludes PPN" (safety first)
        
        8. **TERBILANG PARSING:**
           - Best effort parsing (heuristic-based)
           - If parse fails → SKIP (not FAIL)
           - This is internal consistency check, not GT validation
        
        9. **NUMERIC FORMAT:**
           - Gunakan titik untuk thousand separator di narasi (237.620.142)
           - Gunakan "Rp" prefix untuk mata uang
           - Sebutkan nilai konkrit, bukan placeholder
        
        10. **MULTI-PAGE HANDLING:**
            - Extract from halaman 1 (first occurrence)
            - Ignore duplicate pages (halaman 2+)
            - If values differ between pages → flag as document error
        
        RETURN FINAL JSON ANSWER ONLY.
        """,
"FAKTUR PAJAK":
        """
        FAKTUR PAJAK vs GROUND TRUTH VALIDATION PROMPT - UNIFIED ADAPTIVE VERSION
        ═════════════════════════════════════════════════════════════════════════════
        Document Type: FAKTUR PAJAK (Tax Invoice)
        Validation Mode: STRICT with ROUNDING TOLERANCE for numeric values
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output harus SINGKAT, JELAS, dan BERSAHABAT
        - ROUNDING TOLERANCE: untuk numeric values (PPN calculation bisa ada pembulatan)
        - ZERO TOLERANCE: untuk text/referensi (exact match)
        - OUTPUT STYLE: Tanpa "Ada yang salah" / "Sudah benar", langsung narasi substansi
        - ADAPTIVE: Validate yang ada, mention yang tidak ada dalam keterangan
        
        ⚠️ FAKTUR PAJAK CONTEXT:
        - Faktur Pajak adalah dokumen pajak resmi dari DJP
        - Format beragam: PDF resmi DJP atau screenshot portal e-Invoice
        - DPP = Dasar Pengenaan Pajak (nilai sebelum PPN)
        - PPN = Pajak Pertambahan Nilai (bisa 11% atau 12%, tergantung periode)
        - Total = DPP + PPN
        - DPP Nilai Lain = (11/12) × DPP (field khusus Faktur Pajak)
        - Fokus validasi: Nama Project, Referensi (jika ada), Periode, DPP, PPN, Total
        
        ═════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ═════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        
        ═════════════════════════════════════════════════════════════════════════════
        FAKTUR PAJAK DOCUMENT TEXT (may contain duplicate pages):
        ═════════════════════════════════════════════════════════════════════════════
        
        {faktur_pajak_document_text}
        
        Format Variations:
        - Format 1 (PDF Resmi DJP): Structured layout dengan header, PKP, Pembeli, Detail Transaksi, Summary
        - Format 2 (E-Invoice Portal): Screenshot web portal dengan tabel detail transaksi
        
        Action: Extract from halaman 1 (first occurrence), ignore duplicate pages
        
        ═════════════════════════════════════════════════════════════════════════════
        TASK: Validate FAKTUR PAJAK Document dengan PRESISI TINGGI (Adaptive Unified)
        ═════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: NAMA PROJECT/TRANSAKSI VALIDATION
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi nama Barang Kena Pajak / Jasa Kena Pajak vs GT.judul_project
        
        INSTRUKSI:
        
        1. **Extract Nama Transaksi:**
           
           **Format 1 (PDF Resmi):**
           - Lokasi: Kolom "Nama Barang Kena Pajak / Jasa Kena Pajak"
           - Biasanya di tabel detail transaksi
           - Common patterns:
             * "Penyediaan Admin untuk Polda Kalsel Periode 1 Oktober 2024 s.d 30 September 2024"
             * "Penyediaan Perangkat Pendukung Konektivitas Untuk Dinas Penanaman Modal..."
             * "Penyediaan Layanan Manage Service untuk PT Pelsart Tambang Kencana"
           
           **Format 2 (Portal Web):**
           - Lokasi: Kolom "Nama" dalam tabel "Detail Transaksi"
           - Common patterns:
             * "Penyediaan Tenaga Kerja Paruh Waktu untuk PT Bumi Jaya Juni 2023 sd Mei 2024"
             * "Pengadaan Perangkat untuk PT Alam Tri Abadi Mei Prorate 2023 sd Mei 2024"
             * "Pengadaan EOS dedicated untuk diskominfo kabupaten kutai kartanegara"
           
           - Extract full text nama transaksi
           - Normalize: trim whitespace, case-insensitive
        
        2. **Validate Nama Transaksi (SEMANTIC MATCHING):**
           - Compare dengan GT.judul_project
           - SEMANTIC MATCHING:
             * Case insensitive
             * Trim whitespace
             * Check keyword matching (threshold 60-70%)
             * Nama di Faktur mungkin lebih panjang (include periode)
             * Cukup keyword utama yang sama
           - Example:
             * Faktur: "Penyediaan Admin untuk Polda Kalsel Periode 1 Oktober 2024 s.d 30 September 2024"
             * GT: "Penyediaan Admin untuk Polda Kalsel"
             * Keywords match: Penyediaan, Admin, Polda, Kalsel → PASS
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi nama project/transaksi di Faktur Pajak"
        - Keterangan:
          * **PASS**: "Nama transaksi 'Penyediaan Admin untuk Polda Kalsel Periode 1 Oktober 2024 s.d 30 September 2024' mengandung keyword utama yang sesuai dengan judul project Ground Truth."
          
          * **FAIL**: "Nama transaksi 'Pengadaan Perangkat untuk PT Alam Tri Abadi' tidak mengandung keyword utama dari 'Penyediaan Admin untuk Polda Kalsel' (keyword berbeda: Pengadaan vs Penyediaan, Perangkat vs Admin, PT Alam vs Polda)."
          
          * **FAIL (missing)**: "Nama transaksi tidak ditemukan di Faktur Pajak. Tidak dapat melakukan validasi nama project."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 2: REFERENSI NOMOR VALIDATION (OPTIONAL - ADAPTIVE)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi referensi nomor kontrak/invoice (jika ada di Faktur)
        
        INSTRUKSI:
        
        1. **Check Availability:**
           - Cari referensi nomor di Faktur Pajak
           - Common locations:
             * Footer: "(Referensi: 20209/KU370/GSD-200/2026)"
             * Keterangan tambahan: "Ref: ..."
             * Dalam nama transaksi (jarang)
           
           **IF referensi NOT FOUND:**
           - Return: "Referensi nomor tidak tercantum di Faktur Pajak (optional field)."
           - Status: SKIP (not FAIL)
           - End Stage 2
        
        2. **Extract Referensi Nomor (IF AVAILABLE):**
           - Extract nilai exact (preserve dots, slashes, spaces)
           - Format umum: [PREFIX].[NUMBER]/[CODE]/[YEAR]
           - Example: "20209/KU370/GSD-200/2026", "K.TEL.0050/HK.810/TR6-R600/2024"
        
        3. **Validate Against Ground Truth (EXACT MATCH):**
           - Compare dengan GT.nomor_surat_utama (primary)
           - EXACT match required:
             * Character-by-character comparison
             * Case-sensitive (usually uppercase)
             * Preserve format: dots, slashes, dashes, spaces
             * NO fuzzy match, NO tolerance
           - IF NOT match dengan nomor_surat_utama:
             * Try compare dengan GT.nomor_surat_lainnya (secondary)
           - IF both NOT match → FAIL
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi referensi nomor (optional, adaptive)"
        - Keterangan:
          * **SKIP**: "Referensi nomor tidak tercantum di Faktur Pajak (optional field)."
          
          * **PASS**: "Referensi nomor '20209/KU370/GSD-200/2025' di Faktur match dengan nomor kontrak Ground Truth."
          
          * **FAIL**: "Referensi nomor di Faktur '20209/KU370/GSD-200/2026' tidak match dengan Ground Truth '20209/KU370/GSD-200/2025' (tahun berbeda: 2026 vs 2025)."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 3: PERIODE COVERAGE VALIDATION
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Determine periode coverage (full/partial/single month)
        
        INSTRUKSI:
        
        1. **Extract Periode from Faktur:**
           - Lokasi: Dalam nama transaksi (biasanya di akhir)
           - Common patterns:
             * "Periode 1 Oktober 2024 s.d 30 September 2024" (range - typo common di OCR)
             * "Periode Februari 2025 sd Juni 2025" (range)
             * "Juni 2023 sd Mei 2024" (range)
             * "Periode Januari 2024" (single month)
           
           - Parse start month + year
           - Parse end month + year (if range)
           - IF single month → start = end
        
        2. **Calculate Coverage (COT in thinking block):**
           - Count jumlah bulan dari start ke end
           - Handle year transition (e.g., Nov 2024 - Feb 2025 = 4 bulan)
           - Handle full year (e.g., Juni 2023 - Mei 2024 = 12 bulan)
           - Handle OCR errors (e.g., "1 Oktober 2024 s.d 30 September 2024" seharusnya "1 Oktober 2024 s.d 30 September 2025")
           - Store as: faktur_periode_bulan (integer)
        
        3. **Extract Expected from Ground Truth:**
           - Parse GT.jangka_waktu.start_date → extract month + year
           - Parse GT.jangka_waktu.end_date → extract month + year
           - Parse GT.jangka_waktu.duration → extract number (e.g., "12 Bulan" → 12)
        
        4. **Validate Period Match:**
           - Compare start month + year (Faktur vs GT)
           - Compare end month + year (Faktur vs GT)
           - Compare calculated duration (Faktur vs GT)
           - Note: Cukup match bulan + tahun (ignore tanggal spesifik)
           - Note: Toleransi untuk OCR error (e.g., 2024 vs 2025 jika pattern clear)
        
        5. **Determine Coverage Type:**
           - IF faktur_periode_bulan == GT.duration → **FULL PERIOD**
           - IF faktur_periode_bulan < GT.duration → **PARTIAL PERIOD**
           - IF faktur_periode_bulan == 1 → **SINGLE MONTH**
           - Store coverage_type untuk use in Stage 4
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi periode coverage"
        - Keterangan:
          * **PASS (full)**: "Periode di Faktur 'Oktober 2024 s.d September 2025' (12 bulan) match dengan jangka waktu kontrak (full period coverage)."
          
          * **PASS (partial)**: "Periode di Faktur 'Februari 2025 sd Juni 2025' (5 bulan) adalah partial coverage dari jangka waktu kontrak (12 bulan total)."
          
          * **PASS (single)**: "Periode di Faktur 'Januari 2024' (1 bulan) adalah single month coverage."
          
          * **FAIL (start)**: "Start periode di Faktur 'Februari 2024' tidak match dengan kontrak 'Januari 2024'."
          
          * **FAIL (end)**: "End periode di Faktur 'Juli 2025' tidak match dengan expected 'Juni 2025' untuk coverage 5 bulan."
          
          * **FAIL (not found)**: "Periode tidak ditemukan di nama transaksi Faktur. Tidak dapat menentukan coverage type."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 4: DPP, PPN, & TOTAL VALIDATION (with AUTO-DETECT PPN RATE)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi DPP, Tarif PPN (11% or 12%), PPN amount, dan Total
        
        INSTRUKSI:
        
        1. **Extract Values from Faktur:**
           
           **Format 1 (PDF Resmi):**
           - DPP: Field "Dasar Pengenaan Pajak" atau "Harga Jual / Penggantian / Uang Muka / Termin"
           - Tarif PPN: Tidak explicit stated, perlu detect dari calculation
           - PPN: Field "Jumlah PPN (Pajak Pertambahan Nilai)"
           - Total: DPP + PPN (calculated)
           
           **Format 2 (Portal Web):**
           - DPP: Kolom "DPP" dalam tabel atau "Total Harga"
           - Tarif PPN: Kolom "Tarif PPN" (explicit: "11%" atau "12%")
           - PPN: Kolom "PPN"
           - Total: JUMLAH (Total Harga) atau DPP + PPN
        
        2. **Parse Numeric Values (CRITICAL - IGNORE FORMAT):**
           - Remove all formatting: "Rp", "Rp.", ".", ",", "-", spaces
           - Convert to integer
           - Store:
             * faktur_dpp (integer)
             * faktur_ppn (integer)
             * faktur_tarif_ppn (11 or 12, as integer or float)
             * faktur_total (integer, calculated or extracted)
        
        3. **Detect or Extract Tarif PPN:**
           
           **Method 1: Explicit (Format 2):**
           - IF "Tarif PPN" column has value "11%" atau "12%":
             * Extract directly: tarif_ppn = 11 atau 12
           
           **Method 2: Calculate from PPN/DPP (Format 1):**
           - IF tarif not explicit:
             * Calculate: (faktur_ppn / faktur_dpp) × 100
             * IF result ≈ 11% (±0.5%) → tarif_ppn = 11
             * IF result ≈ 12% (±0.5%) → tarif_ppn = 12
             * IF neither → try both 11% and 12% in validation
           
           **Default Logic:**
           - First try 11%
           - IF not match → try 12%
           - IF both not match → report both attempts
        
        4. **Determine Expected DPP (COT in thinking block):**
           
           **Use results from Stage 3:**
           - coverage_type: FULL, PARTIAL, atau SINGLE
           - faktur_periode_bulan: integer (jumlah bulan)
           
           **Logic:**
           
           **IF coverage_type == FULL PERIOD:**
           - Expected DPP = GT.dpp_raw
           
           **IF coverage_type == PARTIAL PERIOD:**
           - Expected DPP = GT.harga_satuan_raw × faktur_periode_bulan
           
           **IF coverage_type == SINGLE MONTH:**
           - Expected DPP = GT.harga_satuan_raw
        
        5. **Validate DPP (with ROUNDING TOLERANCE):**
           - Compare: faktur_dpp vs expected_dpp
           - ROUNDING TOLERANCE: ±0.5% (untuk accommodate pembulatan)
           - Calculate difference: abs(faktur_dpp - expected_dpp)
           - Calculate percentage: (difference / expected_dpp) × 100
           - IF difference ≤ 0.5% → DPP PASS (within tolerance)
           - IF difference > 0.5% → DPP FAIL
        
        6. **Validate PPN Calculation (with ROUNDING TOLERANCE):**
           - Expected PPN = faktur_dpp × (tarif_ppn / 100)
           - Round to nearest integer
           - Compare: faktur_ppn vs expected_ppn
           - ROUNDING TOLERANCE: ±1 (untuk accommodate pembulatan integer)
           - IF abs(faktur_ppn - expected_ppn) ≤ 1 → PPN PASS
           - IF abs(faktur_ppn - expected_ppn) > 1 → PPN FAIL
        
        7. **Validate Total Calculation (CONSISTENCY CHECK):**
           - Expected Total = faktur_dpp + faktur_ppn
           - IF faktur_total extracted:
             * Compare: faktur_total vs expected_total
             * ROUNDING TOLERANCE: ±1
           - IF faktur_total not extracted:
             * Calculate: faktur_total = faktur_dpp + faktur_ppn
           - Report consistency
        
        8. **Provide Context in Output:**
           - Mention: coverage type (full/partial/single)
           - Mention: Tarif PPN detected (11% or 12%)
           - Mention: calculation method used
           - IF tarif explicit: state "Tarif PPN 11% tercantum di Faktur"
           - IF tarif detected: state "Tarif PPN 11% detected dari calculation"
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi DPP, Tarif PPN, PPN, dan Total"
        - Keterangan:
          * **ALL PASS (11%, full)**: "DPP Rp 76.902.240 sesuai dengan GT.dpp_raw (full period coverage). Tarif PPN 11% detected dari calculation. PPN Rp 8.459.246 correct (11% dari DPP, within rounding tolerance). Total Rp 85.361.486 sesuai dengan DPP + PPN."
          
          * **ALL PASS (12%, partial, explicit)**: "DPP Rp 9.660.000 sesuai dengan perhitungan GT.harga_satuan_raw (Rp 1.932.000) × 5 bulan (partial coverage). Tarif PPN 12% tercantum di Faktur. PPN Rp 1.159.200 correct (12% dari DPP). Total Rp 10.819.200 sesuai dengan DPP + PPN."
          
          * **ALL PASS (single month)**: "DPP Rp 7.486.500 sesuai dengan GT.harga_satuan_raw (single month coverage). Tarif PPN 12% detected. PPN Rp 898.380 correct (12% dari DPP). Total Rp 8.384.880 consistent."
          
          * **FAIL (DPP)**: "DPP di Faktur Rp 75.000.000 tidak sesuai dengan GT.dpp_raw Rp 76.902.240 (full period). Selisih: Rp 1.902.240 (2,47%, melebihi tolerance 0,5%). Tarif PPN 11% detected. PPN calculation based on Faktur DPP correct."
          
          * **FAIL (PPN calculation)**: "DPP Rp 76.902.240 sesuai dengan Ground Truth. Tarif PPN 11% detected. Namun PPN di Faktur Rp 8.000.000 tidak correct, seharusnya Rp 8.459.246 (11% dari DPP). Selisih PPN: Rp 459.246 (melebihi rounding tolerance)."
          
          * **FAIL (tarif unknown)**: "DPP Rp 76.902.240 sesuai dengan Ground Truth. Namun Tarif PPN tidak dapat dideteksi: PPN di Faktur Rp 9.000.000 tidak match dengan 11% (expected Rp 8.459.246) maupun 12% (expected Rp 9.228.269). Perlu klarifikasi tarif yang digunakan."
          
          * **FAIL (missing DPP)**: "DPP tidak ditemukan di Faktur Pajak. Hanya PPN Rp 8.459.246 dan Total tersedia. Tidak dapat validasi DPP vs Ground Truth."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 5: DPP NILAI LAIN VALIDATION (OPTIONAL - ADAPTIVE)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi DPP Nilai Lain (jika ada di Faktur)
        
        INSTRUKSI:
        
        1. **Check Availability:**
           - Field "DPP Nilai Lain" atau "DPP Nilai Lain/DPP" (khusus portal e-Invoice)
           - Biasanya ada di Format 2 (Portal Web), jarang di Format 1 (PDF Resmi)
           
           **IF DPP Nilai Lain NOT FOUND:**
           - Return: "DPP Nilai Lain tidak tersedia di Faktur Pajak (optional field, common di portal e-Invoice)."
           - Status: SKIP (not FAIL)
           - End Stage 5
        
        2. **Extract DPP Nilai Lain (IF AVAILABLE):**
           - Lokasi: Kolom "DPP Nilai Lain/DPP" dalam tabel
           - Extract numeric value
           - Parse: remove formatting, convert to integer
           - Store as: faktur_dpp_nilai_lain (integer)
        
        3. **Calculate Expected DPP Nilai Lain:**
           - Formula: **DPP Nilai Lain = (11/12) × DPP**
           - Get faktur_dpp from Stage 4
           - Calculate: expected_dpp_nilai_lain = (11/12) × faktur_dpp
           - Round to nearest integer
        
        4. **Validate DPP Nilai Lain (with ROUNDING TOLERANCE):**
           - Compare: faktur_dpp_nilai_lain vs expected_dpp_nilai_lain
           - ROUNDING TOLERANCE: ±100 (untuk accommodate pembulatan)
           - Calculate difference: abs(faktur_dpp_nilai_lain - expected_dpp_nilai_lain)
           - Calculate percentage: (difference / expected_dpp_nilai_lain) × 100
           - IF difference ≤ 100 OR percentage ≤ 0.5% → PASS
           - IF difference > 100 AND percentage > 0.5% → FAIL
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi DPP Nilai Lain (optional, adaptive)"
        - Keterangan:
          * **SKIP**: "DPP Nilai Lain tidak tersedia di Faktur Pajak (optional field, common di portal e-Invoice)."
          
          * **PASS**: "DPP Nilai Lain Rp 70.493.720 sesuai dengan formula (11/12) × DPP = (11/12) × Rp 76.902.240 = Rp 70.493.720 (within rounding tolerance)."
          
          * **FAIL**: "DPP Nilai Lain di Faktur Rp 70.000.000 tidak sesuai dengan expected (11/12) × Rp 76.902.240 = Rp 70.493.720. Selisih: Rp 493.720 (0,70%, melebihi tolerance)."
        
        ─────────────────────────────────────────────────────────────────────────
        NOTES & GUIDELINES
        ─────────────────────────────────────────────────────────────────────────
        
        **PARSING NUMERIC VALUES (do in thinking block):**
        - Remove all formatting characters: "Rp", "Rp.", ".", ",", "-", spaces
        - Convert to pure integer for comparison
        - Examples:
          * "76.902.240" → 76902240
          * "Rp. 76.902.240,00" → 76902240
          * "76902240" → 76902240
        - Focus on VALUE, not format (Rp 12.000 = 12000)
        
        **TARIF PPN AUTO-DETECTION (do in thinking block):**
        - Method 1: Explicit dari Faktur (kolom "Tarif PPN": "11%" atau "12%")
        - Method 2: Calculate dari (PPN / DPP) × 100
          * IF result ≈ 11% (±0.5%) → tarif = 11%
          * IF result ≈ 12% (±0.5%) → tarif = 12%
        - Always try 11% first, then 12%
        - Report detected tarif in output with method
        
        **DPP CALCULATION BASED ON COVERAGE (do in thinking block):**
        - FULL PERIOD: expected_dpp = GT.dpp_raw
        - PARTIAL PERIOD: expected_dpp = GT.harga_satuan_raw × bulan
        - SINGLE MONTH: expected_dpp = GT.harga_satuan_raw
        - Always mention coverage type in output
        
        **PPN CALCULATION FORMULA:**
        - PPN = DPP × (Tarif / 100)
        - Where Tarif = 11 atau 12
        - Example: PPN = 76.902.240 × 0.11 = 8.459.246,4 ≈ 8.459.246
        
        **TOTAL CALCULATION FORMULA:**
        - Total = DPP + PPN
        - Example: Total = 76.902.240 + 8.459.246 = 85.361.486
        
        **DPP NILAI LAIN FORMULA:**
        - DPP Nilai Lain = (11/12) × DPP
        - Example: DPP Nilai Lain = (11/12) × 76.902.240 = 70.493.720
        
        **ROUNDING TOLERANCE LOGIC (do in thinking block):**
        - DPP: ±0.5% (untuk accommodate pembulatan dalam perhitungan)
        - PPN: ±1 (untuk accommodate pembulatan integer)
        - Total: ±1 (untuk accommodate pembulatan integer)
        - DPP Nilai Lain: ±100 OR ±0.5%
        - IF within tolerance → PASS dengan mention "within rounding tolerance"
        - IF exceed tolerance → FAIL dengan detail selisih
        
        **SEMANTIC MATCHING FOR NAMA TRANSAKSI (do in thinking block):**
        - Extract keywords dari both Faktur nama and GT.judul_project
        - Common keywords: "Penyediaan", "Pengadaan", "Layanan", "Admin", "Tenaga", "Perangkat", nama instansi, lokasi
        - Ignore filler words: "untuk", "di", "pada", "periode", "sd", "s.d"
        - Check keyword overlap
        - Threshold: at least 60-70% keyword match
        - Case insensitive comparison
        
        **EXACT MATCH VALIDATION (do in thinking block):**
        - Referensi nomor: character-by-character, case-sensitive, zero tolerance
        - Periode dates: month + year match
        - Text fields: NO rounding tolerance
        
        **NUMERIC VALIDATION WITH ROUNDING:**
        - All numeric amounts: integer comparison with rounding tolerance
        - DPP, PPN, Total, DPP Nilai Lain: ada tolerance
        - Formula-based validation: allow ±1 or ±0.5% difference
        
        **FAKTUR FORMAT DETECTION (do in thinking block):**
        - Format 1 (PDF Resmi DJP): Structured layout, NO explicit tarif PPN
        - Format 2 (Portal Web): Table format, ada explicit tarif PPN column
        - Adaptive extraction: handle both formats dengan unified approach
        
        **DUPLICATE PAGE HANDLING:**
        - Extract from halaman 1 (first occurrence) only
        - Ignore halaman 2+ if duplicate
        - Faktur Pajak usually 1 page
        
        **COVERAGE TYPE IMPLICATIONS:**
        - FULL PERIOD: compare dengan GT.dpp_raw
        - PARTIAL PERIOD: calculate GT.harga_satuan_raw × bulan
        - SINGLE MONTH: compare dengan GT.harga_satuan_raw
        - This affects Stage 4 expected DPP calculation
        
        **ADAPTIVE STAGE EXECUTION:**
        - Stage 1: ALWAYS execute (mandatory)
        - Stage 2: execute IF referensi nomor ada di Faktur (SKIP if missing)
        - Stage 3: ALWAYS execute (mandatory)
        - Stage 4: ALWAYS execute (mandatory)
        - Stage 5: execute IF DPP Nilai Lain ada di Faktur (SKIP if missing)
        - SKIP = acceptable, not FAIL
        - Report SKIP status dengan clear reason
        
        **OCR ERROR HANDLING (Periode):**
        - Common OCR error: "1 Oktober 2024 s.d 30 September 2024" (should be 2025)
        - Logic check: IF same month+year untuk start & end → likely error
        - Apply common sense: IF supposed to be 12 months, adjust year
        - Mention in output if correction applied
        
        ═════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT FORMAT (JSON ONLY)
        ═════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_nama_transaksi": {{
            "review": "Validasi nama project/transaksi di Faktur Pajak",
            "keterangan": "[Narasi: Nama transaksi... Keyword matching... Match vs GT...]"
          }},
          
          "stage_2_referensi_nomor": {{
            "review": "Validasi referensi nomor (optional, adaptive)",
            "keterangan": "[Narasi: Referensi nomor... Match vs GT... atau SKIP reason...]"
          }},
          
          "stage_3_periode_coverage": {{
            "review": "Validasi periode coverage",
            "keterangan": "[Narasi: Periode range... Coverage type (full/partial/single)... Match vs GT...]"
          }},
          
          "stage_4_dpp_ppn_total": {{
            "review": "Validasi DPP, Tarif PPN, PPN, dan Total",
            "keterangan": "[Narasi: DPP vs GT... Tarif PPN (11% or 12%)... PPN calculation... Total... Coverage context... Rounding tolerance mention if applicable...]"
          }},
          
          "stage_5_dpp_nilai_lain": {{
            "review": "Validasi DPP Nilai Lain (optional, adaptive)",
            "keterangan": "[Narasi: DPP Nilai Lain... Formula (11/12) × DPP... Match vs expected... atau SKIP reason...]"
          }}
        }}
        
        ═════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES
        ═════════════════════════════════════════════════════════════════════════════
        
        1. **ZERO HALLUCINATION:**
           - Jika tidak ditemukan → catat sebagai missing atau SKIP
           - Jangan guess atau assume nilai
           - Jangan fabricate data
           - Hanya report apa yang benar-benar terlihat di dokumen
        
        2. **ROUNDING TOLERANCE (for numeric values):**
           - DPP: ±0.5% (percentage-based)
           - PPN: ±1 (absolute value)
           - Total: ±1 (absolute value)
           - DPP Nilai Lain: ±100 OR ±0.5%
           - Mention "within rounding tolerance" in output if applicable
           - IF exceed tolerance → FAIL dengan detail selisih dan percentage
        
        3. **ZERO TOLERANCE (for text/referensi):**
           - Referensi nomor: exact character-by-character match
           - Periode dates: exact month + year match
           - NO fuzzy match untuk referensi
        
        4. **TARIF PPN AUTO-DETECTION (11% or 12%):**
           - Extract explicit tarif if available (Format 2)
           - Calculate from (PPN / DPP) × 100 if not explicit (Format 1)
           - Always try 11% first, then 12%
           - Report detected tarif dengan method (explicit/calculated)
           - IF both not match → report both attempts dengan detail
        
        5. **DYNAMIC DPP CALCULATION (Coverage-based):**
           - Expected DPP depends on coverage type:
             * FULL → GT.dpp_raw
             * PARTIAL → GT.harga_satuan_raw × bulan
             * SINGLE → GT.harga_satuan_raw
           - Always mention coverage type in output
           - Formula DPP Nilai Lain: (11/12) × DPP
        
        6. **ADAPTIVE STAGES (Unified Approach):**
           - Stage 1, 3, 4: ALWAYS execute
           - Stage 2 (referensi): SKIP if not found in Faktur
           - Stage 5 (DPP Nilai Lain): SKIP if not found in Faktur
           - Validate apa yang ada, SKIP apa yang tidak ada
           - SKIP ≠ FAIL (it's acceptable)
           - Always mention in keterangan if field not available
        
        7. **OUTPUT NARASI STYLE (NO "Sudah benar"/"Ada yang salah"):**
           - Langsung ke substansi tanpa prefix judgement
           - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning singkat]"
           - Setiap aspek yang divalidasi HARUS dinarasikan hasilnya
           - Jika match: sebutkan nilai dan context
           - Jika tidak match: sebutkan kedua nilai, selisih, percentage (if applicable)
           - Mention rounding tolerance if applicable
           - Keep concise (max 3-4 kalimat per stage)
        
        8. **CHAIN OF THOUGHT (COT) IN THINKING BLOCK:**
           - All parsing: numeric, periode → dalam thinking block
           - All calculations: tarif detection, expected DPP, PPN, differences → dalam thinking block
           - All comparisons dan matching logic → dalam thinking block
           - Output keterangan: hanya hasil akhir dan insight
           - Jangan expose step-by-step process di keterangan
        
        9. **NUMERIC FORMAT IN OUTPUT:**
           - Gunakan titik sebagai thousand separator: "76.902.240"
           - Gunakan prefix "Rp" atau "Rp." untuk mata uang
           - Sebutkan nilai konkrit, bukan placeholder
           - Untuk selisih: "Rp 1.902.240 (2,47%)"
           - Untuk tarif: "Tarif PPN 11%" atau "Tarif PPN 12%"
        
        10. **SEMANTIC MATCHING FOR NAMA TRANSAKSI:**
            - Case insensitive
            - Keyword matching (60-70% threshold)
            - Ignore filler words dan periode info
            - Focus on content keywords
            - Example: "Penyediaan Admin untuk Polda Kalsel Periode..." matches "Penyediaan Admin untuk Polda Kalsel"
        
        11. **EXACT MATCHING FOR REFERENSI:**
            - Character-by-character comparison
            - Case-sensitive
            - Preserve format: dots, slashes, dashes
            - NO fuzzy match, NO tolerance
            - Example: "20209/KU370/GSD-200/2025" must match exactly
        
        12. **FORMULA VALIDATION:**
            - PPN = DPP × (Tarif / 100)
            - Total = DPP + PPN
            - DPP Nilai Lain = (11/12) × DPP
            - Validate each formula dengan rounding tolerance
            - Report which formula passed/failed
        
        13. **DUPLICATE PAGE HANDLING:**
            - Extract from halaman 1 (first occurrence)
            - Ignore halaman 2+ if duplicate
            - Faktur Pajak typically 1 page
        
        14. **CONTEXT PRESERVATION IN OUTPUT:**
            - Always mention coverage type (full/partial/single)
            - Always mention Tarif PPN detected (11% or 12%)
            - Always mention detection method (explicit/calculated)
            - IF rounding applied: state "within rounding tolerance"
            - Provide full context untuk transparency
        
        15. **VALIDATION SEQUENCE:**
            - Execute stages in order: 1 → 2 (if applicable) → 3 → 4 → 5 (if applicable)
            - Don't short-circuit: continue all stages even if early failures
            - Report all results regardless of dependencies
            - Stage 4 uses results from Stage 3 (coverage type)
            - Stage 5 uses results from Stage 4 (DPP value)
        
        16. **MISSING DATA HANDLING:**
            - Nama transaksi missing: report missing, continue other validations
            - Referensi missing: SKIP (optional field)
            - Periode missing: report missing, assume full period for Stage 4
            - DPP/PPN missing: report missing, cannot validate calculations
            - DPP Nilai Lain missing: SKIP (optional field)
        
        17. **JSON OUTPUT COMPLIANCE:**
            - NO text outside JSON structure
            - NO markdown code blocks (```json ... ```)
            - NO explanatory text before/after JSON
            - Valid JSON format: proper escaping, no trailing commas
            - All 5 stage keys must be present (even if SKIP)
        
        18. **CONCISE KETERANGAN (Max 3-4 kalimat per stage):**
            - Stage 1: Nama transaksi + Keyword matching result
            - Stage 2: Referensi nomor + Match result (or SKIP)
            - Stage 3: Periode range + Coverage type + Match result
            - Stage 4: DPP + Tarif PPN + PPN calc + Total + Coverage context + Tolerance mention
            - Stage 5: DPP Nilai Lain + Formula + Match result (or SKIP)
            - Fokus pada insight, bukan process detail
        
        19. **FRIENDLY & PROFESSIONAL TONE:**
            - Avoid harsh language
            - Provide constructive feedback with clear reasons
            - Use neutral framing for failures
            - Maintain professional but friendly tone
            - Example: "tidak sesuai" instead of "salah"
            - Example: "Selisih: Rp X (Y%, melebihi tolerance)" for transparency
        
        20. **FAKTUR PAJAK CONTEXT:**
            - Dokumen pajak resmi dari DJP
            - DPP = nilai sebelum PPN (tax base)
            - PPN = pajak yang dikenakan (11% or 12%)
            - Total = DPP + PPN (final amount)
            - DPP Nilai Lain = (11/12) × DPP (special field)
            - Format beragam: PDF resmi atau portal web
        
        21. **PRIORITY EQUAL:**
            - Semua stages punya priority sama (no hierarchy)
            - FAIL di any stage = issue yang perlu attention
            - Report all stages dengan equal importance
            - Don't prioritize DPP over other fields
        
        22. **COMPARISON PRIORITY:**
            - Nama transaksi: semantic matching dengan GT.judul_project
            - Referensi: exact match dengan GT.nomor_surat_utama (if exists)
            - Periode: match dengan GT.jangka_waktu
            - DPP: based on coverage type (GT.dpp_raw or calculated)
            - PPN: validate calculation dengan detected tarif
            - DPP Nilai Lain: validate formula (11/12) × DPP
        
        23. **THINKING BLOCK USAGE:**
            - Parse numeric values dalam thinking block
            - Detect tarif PPN dalam thinking block
            - Calculate expected values dalam thinking block
            - Calculate differences dan percentages dalam thinking block
            - Determine semantic matching dalam thinking block
            - Extract keywords dalam thinking block
            - Output keterangan: only final results and insights
        
        24. **ROUNDING TOLERANCE FIELDS:**
            - DPP: percentage-based (±0.5%)
            - PPN: absolute value (±1)
            - Total: absolute value (±1)
            - DPP Nilai Lain: absolute (±100) OR percentage (±0.5%)
            - Explicitly mention tolerance in output if within range
        
        25. **EXACT MATCH FIELDS (Zero Tolerance):**
            - Referensi nomor kontrak (if exists)
            - Periode dates (month + year)
            - Text-based fields
        
        ═════════════════════════════════════════════════════════════════════════════
        RETURN FINAL JSON ANSWER ONLY - NO ADDITIONAL TEXT
        ═════════════════════════════════════════════════════════════════════════════
        """,
"BAST":
        """
        BAST (BERITA ACARA SERAH TERIMA) VALIDATION PROMPT - 5 STAGE VERSION
        ═════════════════════════════════════════════════════════════════════════════
        Document Type: BAST (Berita Acara Serah Terima)
        Validation Mode: VALUE-BASED COMPARISON with SEMANTIC MATCHING
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output harus SINGKAT, JELAS, dan BERSAHABAT
        - FOKUS pada MAKNA/VALUE, bukan format literal
        - Handle OTC vs Bulanan dengan conditional logic
        - OUTPUT STYLE: Tanpa "Ada yang salah" / "Sudah benar", langsung narasi substansi
        
        ⚠️ SEMANTIC MATCHING RULES:
        - Angka: "21.252.000" = "21252000" = "Rp 21.252.000" (abaikan format, compare nilai)
        - Jabatan: "Manager" = "Mgr." = "MGR" = "MANAGER" (abaikan case & abbreviation)
        - Nama: "ERNI PURWANTI DEWI" = "Erni Purwanti Dewi" (case insensitive)
        - Tanggal: "02-01-2024" = "2 Januari 2024" = "02 Jan 2024" (parse ke same date)
        - Judul: keyword matching, tidak harus exact 100%
        
        ⚠️ OUTPUT NARASI RULES:
        - Setiap aspek yang divalidasi HARUS dinarasikan hasilnya
        - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning singkat]"
        - Jika match: sebutkan nilai yang match
        - Jika tidak match: sebutkan kedua nilai dan selisih/perbedaan
        - NO "Sudah benar" / "Ada yang salah" di awal kalimat
        - Langsung ke substansi
        
        ═════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ═════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        ═════════════════════════════════════════════════════════════════════════════
        BAST DOCUMENT TEXT (multi-page):
        ═════════════════════════════════════════════════════════════════════════════
        
        {bast_document_text}
        
        ═════════════════════════════════════════════════════════════════════════════
        TASK: Validate BAST Document dengan PRESISI TINGGI (Value-Based)
        ═════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: VALIDATE HEADER & IDENTITAS DOKUMEN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi informasi header BAST (judul, kontrak, nilai)
        
        INSTRUKSI:
        
        1. **Extract Header Information:**
           - Lokasi: Bagian paling atas dokumen (halaman 1)
           - Extract field utama:
             * Pekerjaan / Judul pekerjaan
             * No. Kontrak / No. SP (format: K.TEL.XXXX/HK.XXX/TRXX/YYYY)
             * Tanggal kontrak
             * Nilai kontrak / Nilai SP
           - SKIP (no extraction needed):
             * Nomor BAST Telkom (TEL.XXX...)
             * Nomor BAST Mitra (XXX/...)
             * No. Amandemen
        
        2. **Parse Tanggal Kontrak:**
           - Format bisa: "02 Januari 2024" atau "02-01-2024" atau "2 Jan 2024"
           - Standardize to DD-MM-YYYY for comparison
           - Month name mapping: Januari=01, Februari=02, ..., Desember=12
        
        3. **Parse Nilai Kontrak (CRITICAL - IGNORE FORMAT):**
           - Input bisa: "Rp. 214.072.200,-" atau "214072200" atau "Rp 21.252.000"
           - Process (lakukan di thinking block):
             * Remove: "Rp", "Rp.", ".", ",", "-", whitespace
             * Convert to integer: 214072200
           - Compare INTEGER VALUES only
        
        4. **Validate Against Ground Truth:**
           
           **A. Judul Pekerjaan:**
           - Compare extracted judul dengan GT.judul_project
           - SEMANTIC MATCHING:
             * Case insensitive
             * Trim whitespace
             * Check keyword matching (threshold 60-70%)
             * Cukup keyword utama yang sama
           - Example:
             * BAST: "Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang"
             * GT: "Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang"
             * Keywords match: Penyediaan, Perangkat, Pendukung, Konektivitas, DPMPTSP, Bengkayang
             * PASS
           
           **B. Nomor Kontrak:**
           - Compare extracted nomor dengan GT.nomor_surat_utama
           - EXACT match (case insensitive, trim whitespace)
           - Format umum: K.TEL.XXXX/HK.XXX/TRXX/YYYY
           - Character-by-character comparison
           
           **C. Tanggal Kontrak:**
           - Parse extracted tanggal ke DD-MM-YYYY
           - Parse GT.tanggal_kontrak ke DD-MM-YYYY
           - Compare date VALUES (ignore format)
           - Example: "02 Januari 2024" = "02-01-2024"
           
           **D. Nilai Kontrak:**
           - Parse extracted nilai to integer
           - Parse GT.dpp_raw to integer (atau GT.dpp, sama nilainya)
           - Compare INTEGER values (ignore format)
           - Tolerance: ±1000 (for rounding)
           - Note: Nilai kontrak belum termasuk PPN (compare dengan dpp_raw)
        
        5. **Validation Rules:**
           - Semua 4 field harus ditemukan di dokumen
           - Jika tidak ditemukan: catat sebagai missing
           - Jika mismatch NILAI: sebutkan perbedaan dengan detail
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi header dan identitas dokumen BAST"
        - Keterangan:
          * **ALL PASS**: "Judul pekerjaan 'Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang' sesuai dengan judul project. Nomor kontrak K.TEL.0812/HK.810/TR4-R400/2025 match dengan nomor kontrak di Ground Truth. Tanggal kontrak 21 Januari 2025 sesuai dengan tanggal kontrak 21-01-2025. Nilai kontrak Rp 21.252.000 sesuai dengan DPP kontrak."
          
          * **FAIL (judul)**: "Judul pekerjaan 'Penyediaan Admin untuk Polda' tidak mengandung keyword utama dari judul project 'Penyediaan Perangkat Pendukung Konektivitas' (keyword berbeda: Admin vs Perangkat). Nomor kontrak, tanggal kontrak, dan nilai kontrak semuanya sesuai dengan Ground Truth."
          
          * **FAIL (nomor kontrak)**: "Judul pekerjaan sesuai dengan judul project. Nomor kontrak di BAST 'K.TEL.0050/HK.810/TR6-R600/2024' tidak match dengan nomor kontrak di Ground Truth 'K.TEL.0812/HK.810/TR4-R400/2025'. Tanggal kontrak dan nilai kontrak sesuai."
          
          * **FAIL (tanggal)**: "Judul pekerjaan dan nomor kontrak sesuai. Tanggal kontrak di BAST 02 Januari 2024 tidak match dengan tanggal kontrak di Ground Truth 21 Januari 2025 (selisih 1 tahun 19 hari). Nilai kontrak sesuai."
          
          * **FAIL (nilai)**: "Judul pekerjaan, nomor kontrak, dan tanggal kontrak semuanya sesuai. Nilai kontrak di BAST Rp 20.000.000 tidak match dengan DPP kontrak Rp 21.252.000. Selisih: Rp 1.252.000 (5,89%)."
          
          * **PASS with rounding**: "Judul pekerjaan, nomor kontrak, dan tanggal kontrak semuanya sesuai. Nilai kontrak Rp 21.252.500 sesuai dengan DPP kontrak Rp 21.252.000 dengan pembulatan kecil (selisih Rp 500)."
          
          * **FAIL (missing)**: "Judul pekerjaan sesuai dengan judul project. Nomor kontrak tidak ditemukan di header BAST. Tanggal kontrak dan nilai kontrak ditemukan dengan benar."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 2: VALIDATE TANGGAL BAST
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi tanggal BAST vs tanggal delivery
        
        INSTRUKSI:
        
        1. **Extract Tanggal BAST:**
           - Lokasi: Kalimat "Pada Hari [HARI], Tanggal [ANGKA] Bulan [BULAN] Tahun [TAHUN]"
           - Common patterns:
             * "Pada Hari Selasa, Tanggal Dua Bulan Januari Tahun Dua Ribu Dua Puluh Empat (02/01/2024)"
             * "Pada hari ini Senin tanggal Lima bulan Juni tahun Dua Ribu Dua Puluh Tiga (05-06-2023)"
             * "Pada Hari Sabtu, Tanggal Satu Bulan Februari Tahun Dua Ribu Dua Puluh Lima (01/02/2025)"
           - Extract tanggal, parse ke format date
        
        2. **Parse Tanggal BAST (COT in thinking block):**
           - Handle multiple formats:
             * Number words: "Dua" = 2, "Satu" = 1, "Lima" = 5, dll
             * Month names: "Januari", "Februari", ..., "Desember"
             * Year words: "Dua Ribu Dua Puluh Empat" = 2024
           - Convert to DD-MM-YYYY for comparison
           - Example: "Tanggal Dua Bulan Januari Tahun Dua Ribu Dua Puluh Empat" → 02-01-2024
        
        3. **Validate Against Ground Truth:**
           - Parse extracted tanggal BAST ke DD-MM-YYYY
           - Parse GT.delivery ke DD-MM-YYYY
           - Compare date VALUES (ignore format)
           - IF match: PASS
           - IF NOT match: calculate difference in days
        
        4. **Validation Rules:**
           - Tanggal BAST HARUS match dengan GT.delivery
           - Date value comparison (ignore format dan hari)
           - Focus on DD-MM-YYYY values
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi tanggal BAST vs tanggal delivery"
        - Keterangan:
          * **PASS**: "Tanggal BAST 01 Februari 2025 (Sabtu) sesuai dengan tanggal delivery 01 Februari 2025 di Ground Truth."
          
          * **FAIL (date mismatch)**: "Tanggal BAST 02 Januari 2024 tidak match dengan tanggal delivery 01 Februari 2025 di Ground Truth. Selisih: 1 tahun 30 hari."
          
          * **FAIL (missing)**: "Tanggal BAST tidak ditemukan di dokumen. Tidak dapat melakukan validasi terhadap tanggal delivery."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 3: VALIDATE PENANDATANGAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi penandatangan BAST (Mitra dan Telkom)
        
        INSTRUKSI:
        
        1. **Extract Penandatangan Information:**
           - Lokasi: Section "kami yang bertandatangan dibawah ini"
           - Extract untuk PIHAK PERTAMA (Mitra):
             * Nama (field "Nama :")
             * Jabatan (field "Jabatan :")
             * Perusahaan (dari "mewakili [NAMA PERUSAHAAN]")
           - Extract untuk PIHAK KEDUA (Telkom):
             * Nama (field "Nama :")
             * Jabatan (field "Jabatan :")
        
        2. **Parse Ground Truth Penandatangan (COT in thinking block):**
           - GT.pejabat_penanda_tangan.bast format:
             * "mitra": "NAMA - JABATAN"
             * "telkom": "NAMA - JABATAN"
           - Split by " - " untuk get nama dan jabatan terpisah
           - Example:
             * GT.bast.mitra = "AFFANDHI ARIEF - Ketua"
             * Split → nama: "AFFANDHI ARIEF", jabatan: "Ketua"
        
        3. **Validate Mitra Penandatangan (FOCUS ON JABATAN):**
           - Extract jabatan dari GT.bast.mitra (split by " - ", ambil bagian kedua)
           - Compare dengan extracted jabatan dari BAST
           - SEMANTIC MATCHING untuk abbreviation:
             * "Direktur" = "Dir." = "DIREKTUR" = "Director"
             * "Manager" = "Mgr." = "MGR" = "MANAGER" = "Manajer"
             * "General Manager" = "GM" = "Gen. Manager"
             * "Ketua" = "KETUA" = "Chairman"
           - Case insensitive
           - Focus on JABATAN (nama case insensitive for info only)
        
        4. **Validate Telkom Penandatangan (FOCUS ON JABATAN):**
           - Extract jabatan dari GT.bast.telkom (split by " - ", ambil bagian kedua)
           - Compare dengan extracted jabatan dari BAST
           - Same semantic matching rules
           - Example:
             * GT: "Manager Project Management"
             * BAST: "MANAGER PROJECT MANAGEMENT"
             * PASS (case insensitive match)
        
        5. **Validation Rules:**
           - Kedua penandatangan harus ditemukan
           - JABATAN harus match (semantic)
           - Nama: informational (case insensitive, no strict validation)
           - Jika tidak ditemukan: catat sebagai missing
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi penandatangan BAST (Mitra dan Telkom)"
        - Keterangan:
          * **ALL PASS**: "Penandatangan Mitra adalah AFFANDHI ARIEF dengan jabatan Ketua, sesuai dengan Ground Truth. Penandatangan Telkom adalah ERNI PURWANTI DEWI dengan jabatan Manager Project Management, sesuai dengan Ground Truth."
          
          * **FAIL (mitra jabatan)**: "Penandatangan Mitra adalah AFFANDHI ARIEF dengan jabatan General Manager, tidak match dengan jabatan di Ground Truth (Ketua). Penandatangan Telkom sesuai dengan Ground Truth."
          
          * **FAIL (telkom jabatan)**: "Penandatangan Mitra sesuai dengan Ground Truth. Penandatangan Telkom adalah ERNI PURWANTI DEWI dengan jabatan Senior Manager, tidak match dengan jabatan di Ground Truth (Manager Project Management)."
          
          * **FAIL (both)**: "Penandatangan Mitra adalah TJAHJADI dengan jabatan Direktur, tidak match dengan Ground Truth (seharusnya AFFANDHI ARIEF - Ketua). Penandatangan Telkom adalah MURSALIN dengan jabatan Mgr. Project Operation & Quality Assurance, tidak match dengan Ground Truth (seharusnya ERNI PURWANTI DEWI - Manager Project Management)."
          
          * **PASS (with abbreviation)**: "Penandatangan Mitra sesuai dengan Ground Truth. Penandatangan Telkom adalah ERNI PURWANTI DEWI dengan jabatan Mgr. Project Management, sesuai dengan Ground Truth 'Manager Project Management' (abbreviation handled)."
          
          * **FAIL (missing mitra)**: "Informasi penandatangan Mitra tidak ditemukan di dokumen BAST. Penandatangan Telkom ditemukan dengan benar dan sesuai dengan Ground Truth."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 4: VALIDATE PERNYATAAN & REFERENSI DOKUMEN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi progress 100% dan referensi BAUT/BARD (informational)
        
        INSTRUKSI:
        
        1. **Extract Progress Statement:**
           - Lokasi: Section "PIHAK KEDUA menyatakan MENERIMA..." atau ketentuan
           - Look for statement: "Progress pekerjaan telah mencapai 100%"
           - Variations:
             * "Progres pekerjaan telah mencapai 100%"
             * "Progress pekerjaan 100%"
           - IF found: progress_100 = TRUE
           - IF NOT found: progress_100 = FALSE
        
        2. **Extract Referensi BAUT:**
           - Lokasi: Same section dengan progress
           - Pattern: "Berita Acara Uji Terima tanggal [TANGGAL]"
           - Extract tanggal BAUT (informational only, no validation)
           - Format bisa: "02/01/2024" atau "01/02/2025" atau "5 Juni 2023"
        
        3. **Extract Referensi BARD:**
           - Lokasi: Same section dengan progress
           - Pattern: "Berita Acara Rekonsiliasi Delivery tanggal [TANGGAL]"
           - Extract tanggal BARD (informational only, no validation)
           - Format bisa: "02/01/2024" atau "01/02/2025"
        
        4. **Validate Progress 100%:**
           - Check: progress_100 = TRUE
           - This is CRITICAL validation
           - IF TRUE: PASS
           - IF FALSE: FAIL (progress harus 100%)
        
        5. **Report BAUT & BARD (Informational Only):**
           - NO validation terhadap GT
           - Tanggal BAUT dan BARD bisa berbeda dari GT.delivery
           - Cukup report keberadaan dan tanggal
           - IF not found: mention tidak ditemukan
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi progress pekerjaan dan referensi dokumen pendukung"
        - Keterangan:
          * **ALL PASS**: "Progress pekerjaan disebutkan telah mencapai 100% sesuai requirement. Dokumen mereferensikan Berita Acara Uji Terima (BAUT) tanggal 01/02/2025 dan Berita Acara Rekonsiliasi Delivery (BARD) tanggal 01/02/2025."
          
          * **FAIL (no 100%)**: "Statement progress pekerjaan 100% tidak ditemukan di dokumen BAST. Dokumen mereferensikan BAUT tanggal 02/01/2024 dan BARD tanggal 02/01/2024."
          
          * **PASS (BAUT only)**: "Progress pekerjaan disebutkan telah mencapai 100%. Dokumen mereferensikan BAUT tanggal 5 Juni 2023. Referensi BARD tidak ditemukan."
          
          * **PASS (no references)**: "Progress pekerjaan disebutkan telah mencapai 100%. Referensi ke BAUT dan BARD tidak ditemukan di dokumen (acceptable)."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 5: VALIDATE TABEL LAMPIRAN - DETAIL & PERHITUNGAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi tabel lampiran (detail item, periode, harga, grand total)
        
        INSTRUKSI:
        
        1. **Locate Tabel Lampiran:**
           - Cari section "LAMPIRAN BERITA ACARA SERAH TERIMA" atau halaman 2
           - Ada 2 tabel:
             * Tabel 1: Detail Rincian (item-by-item)
             * Tabel 2: Rekap Harga (summary)
        
        2. **Extract dari Tabel 1 (Detail Rincian):**
           - Kolom yang perlu di-extract:
             * No (item number)
             * Detail Item (nama pekerjaan/layanan)
             * Jumlah & Satuan (1 Paket, 1 Orang, dll)
             * Periode / Masa Layanan (Bulan)
             * Harga Pekerjaan (IDR):
               - **Bulanan**: kolom "Bulanan"
               - **OTC**: kolom "OTC"
               - **Total**: kolom "Total" (per item)
        
        3. **Parse Numeric Values (CRITICAL):**
           - Remove all formatting: "Rp", ".", ",", spaces
           - Convert to integer
           - Examples:
             * "1.932.000" → 1932000
             * "21.252.000" → 21252000
             * "76.902.240" → 76902240
        
        4. **Count Items:**
           - Count jumlah baris item di tabel (exclude header & total)
           - Store as: bast_item_count
        
        5. **Validate Items (CONDITIONAL by Payment Method):**
           
           **IF GT.metode_pembayaran = "OTC":**
           - Expected: 1 item only
           - Check: bast_item_count = 1
           - If > 1: flag as issue
           
           **IF GT.metode_pembayaran = "Bulanan":**
           - Expected: bisa 1 atau multiple items
           - No restriction on item count
        
        6. **Validate Periode/Masa Layanan (for each item):**
           - Extract periode dari kolom "Periode (Bulan)" atau "Masa Layanan (Bln)"
           - Parse: "11" → 11, "12 Bulan" → 12
           - Compare dengan GT.jangka_waktu.duration (extract number dari "N Bulan")
           - ALL items harus punya periode yang sama = GT duration
           - IF GT.metode_pembayaran = "OTC": periode bisa diabaikan atau = GT duration
        
        7. **Validate Harga per Item (CONDITIONAL):**
           
           **IF GT.metode_pembayaran = "Bulanan":**
           - Extract "Harga Bulanan" dari kolom "Bulanan"
           - Parse to integer
           - Compare dengan GT.harga_satuan_raw
           - Tolerance: ±1000
           - ALL items harus punya harga bulanan yang SAMA
           - Calculate "Total" expected: Harga Bulanan × Periode
           - Compare dengan actual "Total" di tabel
           
           **IF GT.metode_pembayaran = "OTC":**
           - Extract "OTC" dari kolom "OTC"
           - Parse to integer
           - Compare dengan GT.dpp_raw
           - Tolerance: ±1000
           - "Total" per item harus SAMA dengan nilai OTC
        
        8. **Extract GRAND TOTAL (dari Tabel 2 - Rekap Harga):**
           - Cari section "Rekap Harga" atau "Total Harga" atau "Total Rekap Harga"
           - Extract row "GRAND TOTAL" atau "TOTAL (Sebelum PPN)" atau "TOTAL (sebelum PPN)"
           - Parse nilai numeric ke integer
           - Store as: bast_grand_total
        
        9. **Validate GRAND TOTAL (3 Methods):**
           
           **Method A: Compare dengan DPP**
           - Compare bast_grand_total vs GT.dpp_raw (integer)
           - Tolerance: ±1000 atau ±0.1%
           
           **Method B: Sum dari Items (Internal Consistency)**
           - Sum all "Total" values dari Tabel 1
           - Compare dengan bast_grand_total
           
           **Method C: Calculate from Parameters**
           - **Bulanan**: Expected = GT.harga_satuan_raw × GT.duration × item_count
           - **OTC**: Expected = GT.dpp_raw
           - Compare dengan bast_grand_total
        
        10. **Validation Priority:**
            - Priority 1: Method A (DPP comparison) - MOST IMPORTANT
            - Priority 2: Method B (sum items) - internal consistency
            - Priority 3: Method C (calculate) - secondary validation
        
        11. **Rounding Tolerance:**
            - Absolute: ±1000 (±Rp.1.000)
            - Percentage: ±0.1%
            - If within tolerance: flag as "pembulatan kecil"
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi tabel lampiran (detail item, periode, harga, total, grand total)"
        - Keterangan:
          * **ALL PASS (Bulanan)**: "Tabel lampiran berisi 1 item dengan periode 11 bulan sesuai jangka waktu kontrak. Harga bulanan Rp 1.932.000 match dengan harga satuan kontrak. Perhitungan total per item: Rp 1.932.000 × 11 bulan = Rp 21.252.000 sudah benar. GRAND TOTAL Rp 21.252.000 sesuai dengan DPP kontrak dan konsisten dengan penjumlahan item."
          
          * **ALL PASS (OTC)**: "Tabel lampiran berisi 1 item untuk pembayaran OTC dengan periode 12 bulan. Nilai OTC Rp 76.902.240 match dengan DPP kontrak. Total per item sama dengan nilai OTC (Rp 76.902.240). GRAND TOTAL Rp 76.902.240 sesuai dengan DPP kontrak."
          
          * **PASS with rounding**: "Tabel lampiran berisi 1 item dengan periode 12 bulan sesuai kontrak. Harga bulanan Rp 7.486.500 sesuai. Total per item Rp 89.838.000 benar (Rp 7.486.500 × 12). GRAND TOTAL Rp 89.838.500 sesuai dengan DPP kontrak Rp 89.838.000 dengan pembulatan kecil (selisih Rp 500)."
          
          * **FAIL (periode)**: "Tabel lampiran berisi 1 item dengan periode 12 bulan sedangkan jangka waktu kontrak adalah 11 bulan (tidak match). Harga bulanan Rp 1.932.000 sesuai. Total per item Rp 23.184.000 (12 × Rp 1.932.000) tidak match dengan ekspektasi 11 bulan. GRAND TOTAL Rp 23.184.000 tidak sesuai dengan DPP kontrak Rp 21.252.000. Selisih: Rp 1.932.000 (1 bulan)."
          
          * **FAIL (harga bulanan)**: "Tabel lampiran berisi 1 item dengan periode 11 bulan sesuai kontrak. Harga bulanan Rp 2.000.000 tidak match dengan harga satuan kontrak Rp 1.932.000. Selisih: Rp 68.000. Total per item Rp 22.000.000 juga tidak sesuai. GRAND TOTAL Rp 22.000.000 tidak match dengan DPP kontrak Rp 21.252.000."
          
          * **FAIL (OTC item count)**: "Tabel lampiran berisi 2 item sedangkan untuk pembayaran OTC seharusnya hanya 1 item. Nilai item pertama Rp 76.902.240 sesuai dengan DPP kontrak. GRAND TOTAL Rp 153.804.480 tidak sesuai karena duplikasi item."
          
          * **FAIL (grand total only)**: "Tabel lampiran detail semuanya benar: 1 item, periode 11 bulan, harga bulanan Rp 1.932.000, total per item Rp 21.252.000. Namun GRAND TOTAL di rekap harga Rp 20.000.000 tidak konsisten dengan penjumlahan item. Selisih: Rp 1.252.000. GRAND TOTAL juga tidak match dengan DPP kontrak."
          
          * **FAIL (missing table)**: "Tabel lampiran tidak ditemukan di dokumen BAST. Sudah dicek halaman 1 dan 2. Tidak dapat melakukan validasi detail item, harga, dan grand total."
        
        ─────────────────────────────────────────────────────────────────────────
        NOTES & GUIDELINES
        ─────────────────────────────────────────────────────────────────────────
        
        **PARSING NUMERIC VALUES (do in thinking block):**
        - Remove all formatting characters: "Rp", "Rp.", ".", ",", "-", spaces
        - Convert to pure integer for comparison
        - Examples:
          * "21.252.000" → 21252000
          * "Rp. 76.902.240,-" → 76902240
          * "214072200" → 214072200
        
        **PARSING DATES (do in thinking block):**
        - Handle multiple formats:
          * "DD-MM-YYYY"
          * "DD Bulan YYYY"
          * "DD/MM/YYYY"
          * Number words: "Dua" = 2, "Satu" = 1, "Lima" = 5
          * Year words: "Dua Ribu Dua Puluh Empat" = 2024
        - Convert month names to numbers:
          * "Januari" → 01, "Februari" → 02, ..., "Desember" → 12
        - Standardize to DD-MM-YYYY for comparison
        
        **PARSING DURATION:**
        - Extract number from text: "12 Bulan" → 12, "11 Bulan" → 11
        - Handle variations: "12 bulan", "12 Bln", "12 bln"
        - Store as integer
        
        **SEMANTIC MATCHING FOR JABATAN (do in thinking block):**
        - Abbreviation equivalents:
          * Manager = Mgr. = MGR = MANAGER = Manajer
          * General Manager = GM = Gen. Manager = General Mgr.
          * Project Manager = PM = Proj. Manager = Project Mgr.
          * Direktur = Dir. = DIREKTUR = Director
          * Ketua = KETUA = Chairman = Chairperson
        - Always case insensitive
        - Focus on semantic meaning
        - Preserve full title: "Manager Project Management"
        
        **SEMANTIC MATCHING FOR JUDUL (do in thinking block):**
        - Extract keywords from both BAST judul and GT.judul_project
        - Common keywords: "Penyediaan", "Perangkat", "Layanan", "Admin", "Tenaga", nama instansi
        - Check keyword overlap
        - Threshold: at least 60-70% keyword match
        
        **ROUNDING TOLERANCE LOGIC (do in thinking block):**
        - Calculate: difference = abs(actual - expected)
        - Calculate: percentage = (difference / expected) × 100 if expected > 0
        - If difference ≤ 1000: within tolerance
        - If percentage ≤ 0.1: within tolerance
        - If within tolerance: flag as "pembulatan kecil"
        
        **OTC vs BULANAN CONDITIONAL:**
        - OTC expectations:
          * Stage 5: 1 item only, OTC value = dpp_raw, Total = OTC
          * GRAND TOTAL = dpp_raw
        - Bulanan expectations:
          * Stage 5: Multiple items OK, Harga Bulanan = harga_satuan_raw
          * Total per item = Harga Bulanan × Periode
          * GRAND TOTAL = sum of all item totals = dpp_raw
        
        **MULTI-PAGE HANDLING:**
        - Halaman 1: Header + Tanggal BAST + Penandatangan + Pernyataan
        - Halaman 2: Lampiran Tabel
        - Extract from ALL pages
        - If duplicate pages: extract from first occurrence
        - If not found: state "sudah dicek halaman 1, 2"
        
        **PROGRESS 100% VALIDATION:**
        - This is CRITICAL requirement
        - Must find explicit statement: "Progress pekerjaan telah mencapai 100%"
        - Variations acceptable: "Progres", "progress", "100%", "seratus persen"
        - If not found: FAIL
        
        **BAUT & BARD REFERENCES:**
        - Informational only (no validation terhadap GT)
        - Tanggal bisa berbeda dari GT.delivery (acceptable)
        - Report keberadaan dan tanggal
        - If not found: acceptable (not all BAST have these references)
        
        **PENANDATANGAN FOCUS:**
        - Primary validation: JABATAN (must match with semantic)
        - Secondary: NAMA (informational, case insensitive)
        - GT.pejabat_penanda_tangan.bast.mitra and bast.telkom
        - Split "NAMA - JABATAN" by " - "
        
        ═════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT FORMAT (JSON ONLY)
        ═════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_header_identitas": {{
            "review": "Validasi header dan identitas dokumen BAST",
            "keterangan": "[Narasi: Judul... Nomor kontrak... Tanggal kontrak... Nilai kontrak...]"
          }},
          
          "stage_2_tanggal_bast": {{
            "review": "Validasi tanggal BAST vs tanggal delivery",
            "keterangan": "[Narasi: Tanggal BAST... vs GT.delivery...]"
          }},
          
          "stage_3_penandatangan": {{
            "review": "Validasi penandatangan BAST (Mitra dan Telkom)",
            "keterangan": "[Narasi: Penandatangan Mitra (nama & jabatan)... Penandatangan Telkom (nama & jabatan)...]"
          }},
          
          "stage_4_pernyataan_referensi": {{
            "review": "Validasi progress pekerjaan dan referensi dokumen pendukung",
            "keterangan": "[Narasi: Progress 100%... Referensi BAUT... Referensi BARD...]"
          }},
          
          "stage_5_tabel_lampiran": {{
            "review": "Validasi tabel lampiran (detail item, periode, harga, total, grand total)",
            "keterangan": "[Narasi: Jumlah item... Periode... Harga bulanan/OTC... Total per item... GRAND TOTAL... Jika ada rounding, sebutkan]"
          }}
        }}
        
        ═════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES
        ═════════════════════════════════════════════════════════════════════════════
        
        1. **ZERO HALLUCINATION:**
           - Jika tidak ditemukan → catat sebagai missing
           - Jangan guess atau assume nilai
           - Jangan fabricate data
        
        2. **VALUE-BASED COMPARISON (NOT FORMAT):**
           - Angka: Compare INTEGER value, ignore format
             * "21.252.000" = "21252000" ✅
           - Jabatan: Semantic match, handle abbreviation
             * "Manager" = "Mgr." ✅
           - Nama: Case insensitive (informational)
           - Tanggal: Parse to date value, compare value
             * "02 Januari 2024" = "02-01-2024" ✅
        
        3. **NARASI OUTPUT (NEW STYLE):**
           - NO "Ada yang salah" / "Sudah benar" di awal kalimat
           - Langsung narasi substansi untuk SETIAP aspek yang divalidasi
           - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning]"
           - Setiap aspek HARUS disebutkan
           - Jika match: sebutkan nilai yang match
           - Jika tidak match: sebutkan kedua nilai dan perbedaan
        
        4. **CONCISE BUT COMPLETE:**
           - Keterangan maksimal 4-5 kalimat per stage
           - SETIAP aspek yang dicheck HARUS disebutkan hasilnya
           - Fokus pada HASIL dan INSIGHT
           - Prioritize critical issues
        
        5. **COT IN THINKING BLOCK:**
           - Semua reasoning, parsing, calculation di thinking block
           - Output hanya hasil akhir per aspek
           - No COT details in keterangan field
        
        6. **ROUNDING AWARENESS:**
           - Tolerance: ±Rp.1.000 atau ±0.1%
           - Jika ada rounding: mention "pembulatan kecil (selisih Rp XXX)"
           - Still describe as match/sesuai if within tolerance
        
        7. **NUMERIC FORMAT IN OUTPUT:**
           - Display dengan thousand separator: "21.252.000"
           - Use "Rp" prefix: "Rp 21.252.000"
           - Sebutkan nilai konkrit
        
        8. **SEMANTIC MATCHING:**
           - Jabatan: Handle ALL abbreviations
           - Judul: Keyword matching, threshold 60-70%
           - Nama: Case insensitive
           - Focus on MEANING
        
        9. **OTC vs BULANAN CONDITIONAL:**
           - Stage 5 has conditional logic
           - Follow OTC vs BULANAN guidelines
           - Different validation per method
        
        10. **MULTI-PAGE AWARENESS:**
            - Extract from ALL pages (1, 2)
            - If not found: state "sudah dicek halaman 1, 2"
        
        11. **DATE FLEXIBILITY:**
            - Handle multiple date formats
            - Parse to standard DD-MM-YYYY
            - Compare date VALUES
        
        12. **CONSISTENCY VALIDATION:**
            - Stage 1 nilai kontrak should match Stage 5 GRAND TOTAL
            - Stage 2 tanggal BAST should match GT.delivery
            - Internal consistency: sum items = GRAND TOTAL
        
        13. **PENANDATANGAN FOCUS:**
            - Primary: JABATAN (must match with semantic)
            - Secondary: NAMA (informational)
            - Use GT.pejabat_penanda_tangan.bast only
        
        14. **PROGRESS 100% CRITICAL:**
            - Must find explicit statement
            - If not found: FAIL
            - This is mandatory requirement
        
        15. **BAUT/BARD INFORMATIONAL:**
            - No validation terhadap GT
            - Report keberadaan dan tanggal
            - Acceptable if not found
        
        RETURN FINAL JSON ANSWER ONLY. NO MARKDOWN, NO EXPLANATION, JUST JSON.
        """,
"BARD":
        """
        BARD vs GROUND TRUTH VALIDATION PROMPT - 5 STAGE VERSION
        ═════════════════════════════════════════════════════════════════════════════
        Document Type: BARD (Berita Acara Rekonsiliasi Delivery)
        Validation Mode: SEMANTIC MATCHING with VALUE-BASED COMPARISON
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output harus SINGKAT, JELAS, dan BERSAHABAT
        - FOKUS pada MAKNA/VALUE, bukan format literal
        - Handle OTC vs Bulanan dengan conditional logic di setiap stage
        
        ⚠️ SEMANTIC MATCHING RULES:
        - Angka: "20.333.223" = "20333223" = "Rp 20.333.223" (abaikan format, compare nilai)
        - Jabatan: "Manager" = "Mgr." = "MGR" = "MANAGER" (abaikan case & abbreviation)
        - Nama: "ERNI PURWANTI DEWI" = "Erni Purwanti Dewi" (case insensitive)
        - Tanggal: "01-02-2025" = "1 Februari 2025" = "01 Feb 2025" (parse ke same date)
        - Judul: keyword matching, tidak harus exact 100%
        
        ═════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ═════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        ═════════════════════════════════════════════════════════════════════════════
        BARD DOCUMENT TEXT (multi-page possible):
        ═════════════════════════════════════════════════════════════════════════════
        
        {bard_document_text}
        
        ═════════════════════════════════════════════════════════════════════════════
        TASK: Validate BARD Document dengan PRESISI TINGGI (Value-Based)
        ═════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: VALIDATE HEADER INFORMATION
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi informasi header di halaman pertama BARD
        
        INSTRUKSI:
        
        1. **Extract Header Information:**
           - Cari section "BERITA ACARA REKONSILIASI DELIVERY (BARD)"
           - Extract 4 field utama:
             * Pekerjaan: judul project
             * No. Kontrak / No. SP / No. Surat Pesanan
             * Tanggal (kontrak)
             * Tanggal BARD (tanggal rekonsiliasi)
        
        2. **Parse Tanggal BARD:**
           - Format bisa: "Pada Hari [HARI], Tanggal [ANGKA] Bulan [BULAN] Tahun [TAHUN]"
           - Atau: "DD-MM-YYYY" atau "DD Bulan YYYY"
           - Extract tanggal, parse ke format standar untuk comparison
           - Contoh: "Satu Bulan Februari Tahun Dua Ribu Dua Puluh Lima" → 01-02-2025
        
        3. **Validate Against Ground Truth (SEMANTIC MATCHING):**
           
           **A. Judul Pekerjaan:**
           - Compare extracted judul dengan GT.judul_project
           - SEMANTIC MATCHING:
             * Case insensitive
             * Trim whitespace
             * Check keyword matching (tidak harus exact 100%)
             * Contoh: "Penyediaan Layanan Manage Service untuk PT Pelsart" 
               MATCH dengan "Penyediaan Layanan Managed Service PT Pelsart Tambang Kencana"
               (keyword utama sama: Penyediaan, Layanan, Manage/Managed, PT Pelsart)
           
           **B. Nomor Kontrak/SP:**
           - Compare extracted nomor dengan GT.nomor_surat_utama
           - EXACT match untuk nomor
           - Contoh: "C.Tel.1940/YN.000/TR4-W300/2024" harus match persis
           
           **C. Tanggal Kontrak:**
           - Parse extracted tanggal ke DD-MM-YYYY
           - Parse GT.tanggal_kontrak ke DD-MM-YYYY
           - Compare nilai tanggal (ignore format)
           - Contoh: "28 Oktober 2024" = "28-10-2024"
           
           **D. Tanggal BARD:**
           - Parse extracted tanggal BARD
           - Parse GT.delivery
           - Compare nilai tanggal
           - Contoh: "Satu Bulan Februari Tahun Dua Ribu Dua Puluh Lima" = "01-02-2025"
        
        4. **Validation Rules:**
           - Semua 4 field harus ditemukan di dokumen
           - Jika tidak ditemukan: catat sebagai missing dengan detail
           - Jika mismatch NILAI (bukan format): catat perbedaan
        
        EXPECTED OUTPUT:
        - Review: "Validasi informasi header dokumen BARD"
        - Keterangan:
          * **PASS**: "Sudah benar. Semua informasi header sesuai: judul pekerjaan, nomor kontrak C.Tel.1940/YN.000/TR4-W300/2024, tanggal kontrak 28-10-2024, dan tanggal BARD 01-02-2025."
          * **FAIL (judul semantic)**: "Ada yang salah. Judul pekerjaan di BARD berbeda substansi dengan kontrak. BARD: '[JUDUL_BARD]', Kontrak: '[JUDUL_GT]'."
          * **FAIL (nomor)**: "Ada yang salah. Nomor kontrak tidak match. BARD: '[NOMOR_BARD]', Kontrak: '[NOMOR_GT]'."
          * **FAIL (tanggal kontrak)**: "Ada yang salah. Tanggal kontrak berbeda. BARD: [TGL_BARD], Kontrak: [TGL_GT]."
          * **FAIL (tanggal BARD)**: "Ada yang salah. Tanggal BARD [TGL_BARD] tidak sesuai dengan tanggal delivery [TGL_GT]."
          * **FAIL (missing)**: "Ada yang salah. Informasi [FIELD_NAME] tidak ditemukan di header BARD."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 2: VALIDATE PENANDATANGAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi nama dan jabatan penandatangan BARD (semantic matching)
        
        INSTRUKSI:
        
        1. **Extract Penandatangan Information:**
           - Cari section dengan "bertanda tangan di bawah ini" atau signature section
           - Biasanya ada 2 pihak:
             * PIHAK PERTAMA (Mitra/Vendor/Kopegtel/PT XXX)
             * PIHAK KEDUA (PT TELKOM INDONESIA)
           - Extract untuk masing-masing:
             * Nama
             * Jabatan
             * Perusahaan
        
        2. **Parse Ground Truth Penandatangan:**
           - GT.pejabat_penanda_tangan.bard.mitra format: "NAMA - JABATAN"
           - GT.pejabat_penanda_tangan.bard.telkom format: "NAMA - JABATAN"
           - Split by " - " untuk get nama dan jabatan terpisah
        
        3. **Validate Mitra (PIHAK PERTAMA) - SEMANTIC MATCHING:**
           
           **Nama:**
           - Case insensitive comparison
           - Trim whitespace
           - Allow minor typo (1-2 char difference)
           - Contoh: "MALADI" = "Maladi" = "maladi"
           
           **Jabatan:**
           - SEMANTIC MATCHING untuk abbreviation:
             * "Manager" = "Mgr." = "MGR" = "MANAGER" = "Manajer"
             * "General Manager" = "GM" = "Gen. Manager"
             * "Project Manager" = "PM" = "Proj. Manager"
             * "Assistant Manager" = "Asst. Manager" = "Ast. Mgr." = "AM"
             * "Senior Manager" = "Sr. Manager" = "Senior Mgr." = "SM"
           - Case insensitive
           - Focus on MEANING, bukan exact text
        
        4. **Validate Telkom (PIHAK KEDUA) - SEMANTIC MATCHING:**
           
           **Nama:**
           - Same rules as Mitra
           - Contoh: "ERNI PURWANTI DEWI" = "Erni Purwanti Dewi"
           
           **Jabatan:**
           - Same abbreviation rules
           - Contoh: "MGR. PROJECT MANAGEMENT" = "Manager Project Management"
           - Must contain "PT TELKOM INDONESIA" or "PT.TELKOM" in company field
        
        5. **Validation Rules:**
           - Kedua penandatangan harus ditemukan
           - Nama: semantic match (case insensitive + trim)
           - Jabatan: semantic match (handle abbreviations)
           - Jika tidak ditemukan: catat sebagai missing
        
        EXPECTED OUTPUT:
        - Review: "Validasi penandatangan BARD (Telkom dan Mitra)"
        - Keterangan:
          * **PASS**: "Sudah benar. Penandatangan dari Telkom (ERNI PURWANTI DEWI - Manager Project Management) dan Mitra (MALADI - Manager) sesuai dengan kontrak."
          * **FAIL (mitra nama)**: "Ada yang salah. Nama penandatangan Mitra berbeda. BARD: '[NAMA_BARD]', Kontrak: '[NAMA_GT]'."
          * **FAIL (mitra jabatan)**: "Ada yang salah. Jabatan penandatangan Mitra berbeda. BARD: '[JABATAN_BARD]', Kontrak: '[JABATAN_GT]'."
          * **FAIL (telkom nama)**: "Ada yang salah. Nama penandatangan Telkom berbeda. BARD: '[NAMA_BARD]', Kontrak: '[NAMA_GT]'."
          * **FAIL (telkom jabatan)**: "Ada yang salah. Jabatan penandatangan Telkom berbeda. BARD: '[JABATAN_BARD]', Kontrak: '[JABATAN_GT]'."
          * **FAIL (missing)**: "Ada yang salah. Informasi penandatangan [PIHAK] tidak ditemukan di BARD."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 3: VALIDATE TABEL LAMPIRAN - PROGRESS & PERIODE
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi tabel lampiran halaman 2 - fokus pada progress dan periode
        
        INSTRUKSI:
        
        1. **Locate Tabel Lampiran:**
           - Cari section "LAMPIRAN BERITA ACARA REKONSILIASI DELIVERY"
           - Atau cari tabel dengan header: "Hasil Rekonsiliasi Perhitungan"
           - Biasanya di halaman 2
        
        2. **Extract dari Tabel 1 (Progress Pekerjaan):**
           - Kolom yang perlu di-extract:
             * No (item number: 1, 2, 3... atau 00010, 00020...)
             * Detail Item (nama pekerjaan/layanan)
             * Jumlah & Satuan (1 Paket, 1 Orang, dll)
             * Periode (Bulan) - bisa berupa angka atau text "N Bulan"
             * No Order/SID/Tgl Aktif/Tgl Aktivasi
             * Progress Pekerjaan (%) - harus 100%
        
        3. **Validate Progress:**
           - Extract progress percentage
           - Must be 100% atau "100"
           - Jika tidak 100%: FAIL dengan progress actual
        
        4. **Validate Periode (VALUE-BASED):**
           - Extract nilai periode dari tabel
           - Parse: "60 Bulan" → 60, "60" → 60, "60 bulan" → 60
           - Compare NILAI dengan GT.jangka_waktu.duration
           - Contoh GT: "60 Bulan" → extract 60
           - Must match VALUE, ignore format
        
        5. **Validate Tanggal Aktivasi (DATE VALUE):**
           - Extract tanggal aktivasi/Tgl Aktif/Tgl Aktivasi
           - Parse ke format date
           - Compare dengan GT.jangka_waktu.start_date atau GT.delivery
           - FORMAT BISA BEDA, tapi NILAI TANGGAL harus sama:
             * "31 Januari 2025" = "31-01-2025" = "2025-01-31"
           - Focus on DATE VALUE
        
        6. **Conditional Logic by Payment Method:**
           - If GT.metode_pembayaran = "OTC":
             * Expect: 1 item saja di tabel
             * Periode bisa diabaikan atau = 1 atau kosong
           - If GT.metode_pembayaran = "Bulanan":
             * Bisa 1 atau multiple items
             * Periode harus match duration
        
        7. **Validate Item Detail (SEMANTIC):**
           - Check apakah Detail Item match dengan GT.judul_project
           - Semantic matching: keyword-based, tidak harus exact
           - Contoh: "Managed Service" match "Manage Service"
        
        EXPECTED OUTPUT:
        - Review: "Validasi detail progress dan periode di tabel lampiran"
        - Keterangan:
          * **PASS (Bulanan)**: "Sudah benar. Progress 100% dengan periode 60 bulan sesuai jangka waktu kontrak. Tanggal aktivasi 31 Januari 2025 sesuai."
          * **PASS (OTC)**: "Sudah benar. Progress 100% untuk 1 item OTC dengan tanggal aktivasi 01 Oktober 2023 sesuai."
          * **FAIL (progress)**: "Ada yang salah. Progress pekerjaan di BARD hanya [X]%, seharusnya 100%."
          * **FAIL (periode value)**: "Ada yang salah. Periode di BARD [X] bulan tidak sesuai dengan jangka waktu kontrak [Y] bulan."
          * **FAIL (tanggal value)**: "Ada yang salah. Tanggal aktivasi di BARD [TGL_BARD] tidak sesuai dengan [TGL_GT]."
          * **FAIL (item count OTC)**: "Ada yang salah. Ditemukan [X] item untuk pembayaran OTC, seharusnya hanya 1 item."
          * **FAIL (missing tabel)**: "Ada yang salah. Tabel lampiran tidak ditemukan di dokumen BARD (sudah cek halaman 2)."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 4: VALIDATE TABEL LAMPIRAN - HARGA & PERHITUNGAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi nilai harga di tabel lampiran (VALUE-BASED, ignore format)
        
        INSTRUKSI:
        
        1. **Locate Tabel 2 (Nilai Progress Pekerjaan):**
           - Cari tabel dengan header: "Hasil Rekonsiliasi Perhitungan Nilai Progress Pekerjaan"
           - Atau cari kolom: "Harga Pekerjaan (IDR)"
           - Biasanya di bawah Tabel 1
        
        2. **Extract dari Tabel 2:**
           - Kolom yang perlu di-extract:
             * No
             * Detail Item
             * Jumlah & Satuan
             * Periode (Bulan)
             * Harga Pekerjaan (IDR) - bisa ada subkolom:
               - Bulanan (untuk metode Bulanan)
               - OTC (untuk metode OTC)
               - Total (per item)
        
        3. **Parse Numeric Values (CRITICAL - IGNORE FORMAT):**
           - Input bisa: "1.566.667" atau "1566667" atau "Rp 1.566.667" atau "1,566,667"
           - Process (lakukan di thinking block):
             * Remove: "Rp", ".", ",", whitespace
             * Convert to integer: 1566667
           - Compare INTEGER VALUES only
           - Contoh:
             * "Rp 20.333.223" → parse jadi 20333223
             * "20333223" → parse jadi 20333223
             * Both are EQUAL (value sama, format beda)
        
        4. **Determine Expected Values:**
           - If GT.metode_pembayaran = "Bulanan":
             * Expected Harga Bulanan VALUE = GT.harga_satuan_raw (integer)
             * Expected Total per item = Harga Bulanan × Periode
           - If GT.metode_pembayaran = "OTC":
             * Expected OTC/Total VALUE = GT.dpp_raw (integer)
             * No calculation needed
        
        5. **Validate Harga Bulanan/OTC (VALUE COMPARISON):**
           - Extract harga bulanan atau OTC dari tabel
           - Parse to integer (remove all formatting)
           - Compare INTEGER dengan GT expected value
           - Tolerance: ±1000 (for rounding, equivalent to ±Rp.1.000)
           - If mismatch beyond tolerance: calculate difference
        
        6. **Validate Total per Item (VALUE + CALCULATION):**
           - Extract total dari kolom Total
           - Parse to integer
           - Conditional validation:
             * **Bulanan**: Check if Total = Bulanan × Periode (with tolerance)
             * **OTC**: Check if Total = OTC value (should be same)
           - Rounding tolerance: ±1000 atau percentage ±0.1%
        
        7. **Validate Consistency Across Items:**
           - Jika multiple items: check semua items punya harga yang sama
           - For Bulanan: semua harga bulanan (VALUE) harus sama
           - For OTC: biasanya 1 item saja
        
        8. **Rounding Tolerance Check:**
           - Calculate absolute difference: abs(actual - expected)
           - Calculate percentage: difference / expected
           - Within tolerance if: difference ≤ 1000 OR percentage ≤ 0.001
           - If within tolerance: PASS but flag "Perlu ditinjau"
        
        EXPECTED OUTPUT:
        - Review: "Validasi nilai harga dan total di tabel lampiran"
        - Keterangan:
          * **PASS (Bulanan)**: "Sudah benar. Harga bulanan Rp 1.566.667 × 60 bulan = Rp 94.000.020 sesuai dengan harga satuan kontrak."
          * **PASS (OTC)**: "Sudah benar. Total OTC Rp 76.902.240 sesuai dengan DPP kontrak."
          * **PASS (with rounding)**: "Sudah benar. Harga bulanan Rp 1.566.667 sesuai kontrak. Total Rp 94.000.020 dengan pembulatan kecil (selisih Rp 500). Perlu ditinjau."
          * **FAIL (harga value)**: "Ada yang salah. Harga bulanan di BARD Rp [BARD] tidak match dengan kontrak Rp [GT]. Selisih: Rp [DIFF]."
          * **FAIL (total calculation)**: "Ada yang salah. Total di BARD Rp [BARD] tidak sesuai perhitungan Rp [BULANAN] × [PERIODE] = Rp [EXPECTED]. Selisih: Rp [DIFF]."
          * **FAIL (OTC value)**: "Ada yang salah. Total OTC di BARD Rp [BARD] tidak match dengan DPP Rp [GT]. Selisih: Rp [DIFF]."
          * **FAIL (inconsistent)**: "Ada yang salah. Item [NO] memiliki harga Rp [NILAI1] berbeda dengan item lain Rp [NILAI2]."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 5: VALIDATE GRAND TOTAL
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Cross-validate GRAND TOTAL dengan DPP (VALUE-BASED)
        
        INSTRUKSI:
        
        1. **Extract GRAND TOTAL (PARSE VALUE):**
           - Cari baris dengan keyword "GRAND TOTAL" di bagian bawah tabel
           - Biasanya setelah semua item di Tabel 2
           - Extract nilai numerik
           - PARSE to integer (ignore format):
             * "94.000.020" → 94000020
             * "94000020" → 94000020
             * "Rp 94.000.020" → 94000020
           - Compare INTEGER VALUES
        
        2. **Calculate Expected Total (3 Methods):**
           
           **Method A: DPP Comparison (Full Period)**
           - Expected Total A = GT.dpp_raw (integer)
           - This assumes BARD covers full jangka waktu kontrak
           
           **Method B: Sum from Items**
           - Sum all "Total" values dari semua items di Tabel 2
           - Parse each to integer, then sum
           - Expected Total B = sum of all item totals
           
           **Method C: Calculate from Parameters**
           - For Bulanan: 
             * Parse GT.harga_satuan_raw → integer
             * Parse GT.jangka_waktu.duration → integer (extract number from "N Bulan")
             * Expected Total C = harga_satuan_raw × duration
           - For OTC: 
             * Expected Total C = GT.dpp_raw (same as Method A)
        
        3. **Validation Logic (Priority Order - VALUE COMPARISON):**
           
           **Priority 1: Compare dengan DPP (Method A)**
           - Parse GRAND TOTAL to integer
           - Parse GT.dpp_raw to integer
           - If GRAND TOTAL = DPP → PASS (exact match)
           - If within tolerance → PASS with rounding note
           
           **Priority 2: Compare dengan Sum Items (Method B)**
           - If GRAND TOTAL = sum of items → PASS (internal consistency)
           - This validates tabel calculation is correct
           
           **Priority 3: Compare dengan Calculated (Method C)**
           - If GRAND TOTAL = harga_satuan × periode → PASS (parameter match)
           
           **If all 3 methods FAIL:**
           - Calculate differences from all methods
           - Report most significant discrepancy
        
        4. **Rounding Tolerance (on VALUE):**
           - Tolerance absolute: ±1000 (equivalent to ±Rp.1.000)
           - Tolerance percentage: ±0.001 (equivalent to ±0.1%)
           - Check:
             * difference = abs(actual_int - expected_int)
             * percentage_diff = difference / expected_int if expected_int > 0
             * If difference ≤ 1000: status = "PASS with rounding"
             * Elif percentage_diff ≤ 0.001: status = "PASS with rounding"
             * Else: status = "FAIL"
        
        5. **OTC Special Handling:**
           - For OTC: GRAND TOTAL must match GT.dpp_raw (within tolerance)
           - No complex calculation needed
           - Method A and Method C should give same result
           - If mismatch beyond tolerance: FAIL
        
        6. **Validate Text Statement (OPTIONAL):**
           - Cari kalimat seperti: "setara dengan harga pekerjaan sebesar Rp. [NILAI]"
           - Extract nilai dari text narasi
           - Parse to integer
           - Compare dengan GRAND TOTAL di tabel (VALUE)
           - Should be consistent
        
        EXPECTED OUTPUT:
        - Review: "Validasi GRAND TOTAL dengan DPP dan perhitungan kontrak"
        - Keterangan:
          * **PASS (exact)**: "Sudah benar. GRAND TOTAL Rp 94.000.020 match dengan DPP kontrak (full period coverage)."
          * **PASS (calculated)**: "Sudah benar. GRAND TOTAL Rp 94.000.020 sesuai perhitungan Rp 1.566.667 × 60 bulan."
          * **PASS (sum)**: "Sudah benar. GRAND TOTAL Rp 94.000.020 sesuai penjumlahan semua item di tabel."
          * **PASS (with rounding)**: "Sudah benar. GRAND TOTAL Rp 94.000.020 sesuai DPP dengan pembulatan kecil (selisih Rp 500). Perlu ditinjau."
          * **FAIL (multi-method)**: "Ada yang salah. GRAND TOTAL Rp [BARD] tidak match: (1) DPP Rp [DPP] (selisih Rp [DIFF1]), (2) Perhitungan Rp [CALC] (selisih Rp [DIFF2])."
          * **FAIL (OTC)**: "Ada yang salah. GRAND TOTAL Rp [BARD] tidak sama dengan DPP Rp [DPP] untuk OTC. Selisih: Rp [DIFF]."
          * **FAIL (text inconsistent)**: "Ada yang salah. GRAND TOTAL di tabel Rp [TABEL] tidak match dengan nilai di narasi Rp [NARASI]."
          * **FAIL (not found)**: "Ada yang salah. GRAND TOTAL tidak ditemukan di dokumen BARD (sudah cek halaman 2)."
        
        ─────────────────────────────────────────────────────────────────────────
        NOTES & GUIDELINES
        ─────────────────────────────────────────────────────────────────────────
        
        **PARSING NUMERIC VALUES (do in thinking block):**
        - Remove all formatting characters: "Rp", ".", ",", spaces
        - Convert to pure integer for comparison
        - Examples:
          * "1.566.667" → 1566667
          * "Rp 20.333.223" → 20333223
          * "94000020" → 94000020
        
        **PARSING DATES (do in thinking block):**
        - Handle multiple formats:
          * "DD-MM-YYYY"
          * "DD Bulan YYYY"
          * "Satu Bulan Februari Tahun Dua Ribu Dua Puluh Lima"
        - Convert word numbers to digits:
          * "Satu" → 1
          * "Dua Ribu Dua Puluh Lima" → 2025
        - Convert month names to numbers:
          * "Januari" → 01
          * "Februari" → 02
        - Standardize to DD-MM-YYYY for comparison
        
        **SEMANTIC MATCHING FOR JABATAN (do in thinking block):**
        - Abbreviation equivalents:
          * Manager = Mgr. = MGR = MANAGER = Manajer
          * General Manager = GM = Gen. Manager = General Mgr.
          * Project Manager = PM = Proj. Manager = Project Mgr.
          * Assistant Manager = Asst. Manager = Ast. Mgr. = AM
          * Senior Manager = Sr. Manager = Senior Mgr. = SM
        - Always case insensitive
        - Focus on semantic meaning
        
        **ROUNDING TOLERANCE LOGIC (do in thinking block):**
        - Calculate: difference = abs(actual - expected)
        - Calculate: percentage = difference / expected
        - If difference ≤ 1000: within tolerance
        - If percentage ≤ 0.001: within tolerance
        - If within tolerance: flag as "Perlu ditinjau"
        
        **OTC vs BULANAN CONDITIONAL:**
        - OTC expectations:
          * Stage 3: 1 item only, periode bisa diabaikan
          * Stage 4: Harga = dpp_raw
          * Stage 5: GRAND TOTAL = dpp_raw
        - Bulanan expectations:
          * Stage 3: Multiple items OK, periode must match duration
          * Stage 4: Harga Bulanan = harga_satuan_raw
          * Stage 5: GRAND TOTAL = harga_satuan_raw × duration
        
        **MULTI-PAGE HANDLING:**
        - Halaman 1: Header + Penandatangan
        - Halaman 2: Lampiran + Tabel + GRAND TOTAL
        - Extract from all pages
        - If not found: state "sudah cek halaman X"
        
        ═════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT FORMAT (JSON ONLY - KEEP IT CONCISE)
        ═════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_header": {{
            "review": "Validasi informasi header dokumen BARD",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah dengan detail minimal]"
          }},
          
          "stage_2_penandatangan": {{
            "review": "Validasi penandatangan BARD (Telkom dan Mitra)",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah dengan detail minimal]"
          }},
          
          "stage_3_progress_periode": {{
            "review": "Validasi detail progress dan periode di tabel lampiran",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah dengan detail minimal]"
          }},
          
          "stage_4_harga_perhitungan": {{
            "review": "Validasi nilai harga dan total di tabel lampiran",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah dengan detail minimal. Jika ada rounding, sebutkan]"
          }},
          
          "stage_5_grand_total": {{
            "review": "Validasi GRAND TOTAL dengan DPP dan perhitungan kontrak",
            "keterangan": "[Singkat, bersahabat, jelas: benar/salah dengan detail minimal. Jika ada rounding, sebutkan untuk ditinjau]"
          }}
        }}
        
        ═════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES
        ═════════════════════════════════════════════════════════════════════════════
        
        1. **ZERO HALLUCINATION:**
           - Jika tidak ditemukan → catat sebagai missing
           - Jangan guess atau assume nilai
           - Jangan fabricate data
        
        VALUE-BASED COMPARISON (NOT FORMAT):
        
        Angka: Compare INTEGER value, ignore format
        
        "20.333.223" = "20333223" ✅
        Parse: remove Rp, dots, commas, spaces → integer
        
        
        Jabatan: Semantic match, handle abbreviation
        
        "Manager" = "Mgr." = "MGR" ✅
        
        
        Nama: Case insensitive
        
        "ERNI PURWANTI DEWI" = "Erni Purwanti Dewi" ✅
        
        
        Tanggal: Parse to date value, compare value
        
        "01 Februari 2025" = "01-02-2025" ✅
        
        
        
        
        CONCISE OUTPUT:
        
        Keterangan maksimal 2-3 kalimat per stage
        Fokus pada HASIL validasi dan INSIGHT
        Jika benar: bilang benar dengan nilai konkrit
        Jika salah: jelaskan apa yang salah dan kenapa
        NO detailed process explanation in output
        
        
        FRIENDLY TONE:
        
        "Sudah benar" bukan "Valid" atau "Passed"
        "Ada yang salah" bukan "Invalid" atau "Failed"
        "Perlu ditinjau" untuk edge cases dengan rounding
        Bahasa Indonesia yang natural dan ramah
        
        
        COT IN THINKING BLOCK:
        
        Semua reasoning, parsing, calculation di thinking block
        Use the parsing and matching logic described in NOTES
        Output hanya hasil akhir dan insight
        No COT details in keterangan field
        
        
        ROUNDING AWARENESS:
        
        Tolerance: ±Rp.1.000 atau ±0.1%
        Jika ada rounding: flag dengan "Perlu ditinjau"
        Sebutkan selisih actual: "selisih Rp 500"
        Still PASS if within tolerance
        
        
        NUMERIC FORMAT IN OUTPUT:
        
        Display dengan thousand separator: "94.000.020"
        Use "Rp" prefix: "Rp 94.000.020"
        Sebutkan nilai konkrit, bukan placeholder
        Show actual values from documents
        
        
        SEMANTIC MATCHING:
        
        Jabatan: Handle ALL abbreviations as described in NOTES
        Judul: Keyword matching, tidak exact 100%
        Nama: Case insensitive, trim whitespace
        Focus on MEANING, not literal text
        
        
        OTC vs BULANAN CONDITIONAL:
        
        Every stage has conditional logic
        Follow OTC vs BULANAN guidelines in NOTES
        Document the logic clearly in thinking block
        
        
        MULTI-PAGE AWARENESS:
        
        Extract from ALL pages
        Follow multi-page handling guidelines in NOTES
        If not found: explicitly state "sudah cek halaman X"
        
        
        DATE FLEXIBILITY:
        
        Handle multiple date formats as described in NOTES
        Parse to standard format in thinking block
        Compare date VALUES, not formats
        
        
        CONSISTENCY VALIDATION:
        
        Cross-validate between stages
        Stage 4 total should match Stage 5 GRAND TOTAL
        If internal inconsistency: flag as issue
        Validate table calculation logic
        
        RETURN FINAL JSON ANSWER ONLY. NO MARKDOWN, NO EXPLANATION, JUST JSON.
        """,
"BAUT":
        """
        BAUT (BERITA ACARA UJI TERIMA) VALIDATION PROMPT - 3 STAGE VERSION
        ═════════════════════════════════════════════════════════════════════════════
        Document Type: BAUT (Berita Acara Uji Terima)
        Validation Mode: VALUE-BASED COMPARISON with SEMANTIC MATCHING
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output harus SINGKAT, JELAS, dan BERSAHABAT
        - FOKUS pada MAKNA/VALUE, bukan format literal
        - OUTPUT STYLE: Tanpa "Ada yang salah" / "Sudah benar", langsung narasi substansi
        
        ⚠️ SEMANTIC MATCHING RULES:
        - Angka: "12" = "12 Bulan" = "12 bulan" (extract numeric value)
        - Jabatan: "Manager" = "Mgr." = "MGR" = "MANAGER" (abaikan case & abbreviation)
        - Nama: Ignore dalam comparison, hanya compare JABATAN
        - Tanggal: "01-02-2025" = "01 Februari 2025" = "1 Feb 2025" (parse ke same date)
        - Judul: keyword matching, tidak harus exact 100%
        
        ⚠️ OUTPUT NARASI RULES:
        - Setiap aspek yang divalidasi HARUS dinarasikan hasilnya
        - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning singkat]"
        - Jika match: sebutkan nilai yang match
        - Jika tidak match: sebutkan kedua nilai dan selisih/perbedaan
        - NO "Sudah benar" / "Ada yang salah" di awal kalimat
        - Langsung ke substansi: "Nomor kontrak X sesuai dengan..." bukan "Sudah benar. Nomor kontrak..."
        
        ═════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ═════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        ═════════════════════════════════════════════════════════════════════════════
        BAUT DOCUMENT TEXT (multi-page):
        ═════════════════════════════════════════════════════════════════════════════
        
        {baut_document_text}
        
        ═════════════════════════════════════════════════════════════════════════════
        TASK: Validate BAUT Document dengan PRESISI TINGGI (Value-Based)
        ═════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: VALIDATE HEADER & IDENTITAS DOKUMEN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi informasi header di halaman pertama BAUT
        
        INSTRUKSI:
        
        1. **Extract Header Information:**
           - Lokasi: Bagian paling atas dokumen (halaman 1)
           - Extract 3 field utama:
             * Pekerjaan/Judul (setelah "Pekerjaan :")
             * Nomor Kontrak (setelah "No. Kontrak :" atau "No. SP :")
             * Tanggal BAUT (setelah "Tanggal :" atau dalam kalimat "Pada hari...")
           
           Pattern umum:
           ```
           BERITA ACARA UJI TERIMA (BAUT)
           Pekerjaan : [JUDUL PEKERJAAN]
           No. Kontrak : [NOMOR KONTRAK]
           Tanggal : [TANGGAL]
           ```
           
           Atau dalam paragraf:
           ```
           Pada hari [HARI] tanggal [TANGGAL LENGKAP], telah dilaksanakan Uji Terima...
           ```
        
        2. **Parse Judul Pekerjaan:**
           - Extract text lengkap setelah "Pekerjaan :"
           - Trim whitespace
           - Case insensitive untuk comparison
        
        3. **Parse Nomor Kontrak:**
           - Extract nomor kontrak setelah "No. Kontrak :" atau "No. SP :"
           - Format umum: K.TEL.XXXX/HK.XXX/TRXX/YYYY
           - EXACT match required (case insensitive, trim whitespace)
        
        4. **Parse Tanggal BAUT:**
           - Bisa ada 2 lokasi:
             * Di field "Tanggal : DD-MM-YYYY" atau "Tanggal : DD Bulan YYYY"
             * Di paragraf: "Pada hari [HARI], tanggal [TANGGAL LENGKAP]"
           - Handle multiple formats:
             * "21 Januari 2025" → 21-01-2025
             * "21-01-2025" → 21-01-2025
             * "Dua Puluh Satu Bulan Januari Tahun Dua Ribu Dua Puluh Lima" → 21-01-2025
           - Standardize to DD-MM-YYYY for comparison
        
        5. **Validate Against Ground Truth (VALUE-BASED):**
           
           **A. Judul Pekerjaan:**
           - Compare extracted judul dengan GT.judul_project
           - SEMANTIC MATCHING:
             * Case insensitive
             * Trim whitespace
             * Keyword matching (tidak harus exact 100%)
             * Check keyword utama yang sama (threshold 60-70%)
           - Contoh: 
             * BAUT: "Pengadaan Tenaga TOS (Technician On Site) untuk Diskominfo Kukar"
             * GT: "Pengadaan Tenaga TOS untuk Diskominfo Kukar"
             * Keywords match: Pengadaan, Tenaga, TOS, Diskominfo, Kukar → PASS
           
           **B. Nomor Kontrak:**
           - Compare extracted nomor dengan GT.nomor_surat_utama
           - EXACT match (case insensitive, trim whitespace)
           - Format: K.TEL.XXXX/HK.XXX/TRXX/YYYY
           
           **C. Tanggal BAUT:**
           - Parse extracted tanggal ke DD-MM-YYYY
           - Parse GT.tanggal_kontrak ke DD-MM-YYYY
           - Compare date VALUES (ignore format)
           - If mismatch: calculate difference in days
        
        6. **Validation Rules:**
           - Semua 3 field harus ditemukan di dokumen
           - Jika tidak ditemukan: catat sebagai missing dengan detail lokasi pencarian
           - Jika mismatch NILAI (bukan format): sebutkan perbedaan konkrit
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi header dan identitas dokumen BAUT"
        - Keterangan:
          * **ALL PASS**: "Judul pekerjaan 'Pengadaan Tenaga TOS untuk Diskominfo Kukar' mengandung keyword utama yang sesuai dengan judul project di Ground Truth. Nomor kontrak 'K.TEL.0050/HK.810/TR6-R600/2024' match dengan nomor kontrak di Ground Truth. Tanggal BAUT 02 Januari 2024 sesuai dengan tanggal kontrak 02-01-2024."
          
          * **FAIL (judul semantic)**: "Judul pekerjaan 'Penyediaan Admin untuk Polda Kalsel' tidak mengandung keyword utama dari judul project 'Pengadaan Tenaga TOS untuk Diskominfo Kukar' (keyword berbeda: Admin vs TOS, Polda vs Diskominfo). Nomor kontrak dan tanggal BAUT sesuai dengan Ground Truth."
          
          * **FAIL (nomor kontrak)**: "Judul pekerjaan sesuai dengan judul project. Nomor kontrak 'K.TEL.0050/HK.810/TR6-R600/2023' tidak match dengan nomor kontrak di Ground Truth 'K.TEL.0050/HK.810/TR6-R600/2024' (tahun berbeda: 2023 vs 2024). Tanggal BAUT sesuai."
          
          * **FAIL (tanggal)**: "Judul pekerjaan dan nomor kontrak sesuai dengan Ground Truth. Tanggal BAUT 05 Januari 2024 tidak match dengan tanggal kontrak 02 Januari 2024 (selisih 3 hari, BAUT lebih lambat)."
          
          * **FAIL (missing)**: "Judul pekerjaan dan tanggal BAUT ditemukan dengan benar. Nomor kontrak tidak ditemukan di header dokumen BAUT (sudah dicek bagian 'No. Kontrak' dan 'No. SP')."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 2: VALIDATE PENANDATANGAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi penandatangan BAUT (Telkom & Mitra)
        
        INSTRUKSI:
        
        1. **Locate Penandatangan Section:**
           - Lokasi: Halaman 1, setelah paragraf pembuka
           - Pattern umum:
             ```
             i. Nama  : [NAMA PIHAK PERTAMA]
                Jabatan : [JABATAN PIHAK PERTAMA]
                Dalam hal ini mewakili [NAMA MITRA], selanjutnya disebut Sebagai PIHAK PERTAMA
             
             ii. Nama  : [NAMA PIHAK KEDUA]
                 Jabatan : [JABATAN PIHAK KEDUA]
                 Dalam hal ini mewakili PT.TELKOM INDONESIA (PERSERO) Tbk, selanjutnya disebut Sebagai PIHAK KEDUA
             ```
           
           Note: 
           - PIHAK PERTAMA = Mitra
           - PIHAK KEDUA = Telkom
        
        2. **Extract Penandatangan Information:**
           
           **A. Penandatangan Mitra (PIHAK PERTAMA):**
           - Extract: Nama + Jabatan
           - Identify: yang mewakili Mitra (bukan Telkom)
           - Pattern: "mewakili [NAMA MITRA]" atau "Kopegtel" atau "PT XXX"
           
           **B. Penandatangan Telkom (PIHAK KEDUA):**
           - Extract: Nama + Jabatan
           - Identify: yang mewakili "PT.TELKOM INDONESIA (PERSERO) Tbk"
           - Pattern: "mewakili PT.TELKOM" atau "TELKOM INDONESIA"
        
        3. **Parse Ground Truth Penandatangan:**
           - GT.pejabat_penanda_tangan.baut format:
             ```json
             {{
               "telkom": "NAMA - JABATAN",
               "mitra": "NAMA - JABATAN"
             }}
             ```
           - Split by " - " untuk get nama dan jabatan
           - HANYA compare JABATAN (nama diabaikan)
        
        4. **Validate Telkom Penandatangan (SEMANTIC MATCHING):**
           - Extract jabatan Telkom dari GT (split by " - ", ambil bagian kedua)
           - Compare dengan extracted jabatan Telkom dari BAUT
           - SEMANTIC MATCHING:
             * "Manager" = "Mgr." = "MGR" = "MANAGER" = "Manajer"
             * "Manager Project Management" = "Mgr. Project Management" = "Manager Proj. Mgmt"
             * "Manager Business & Enterprise Service" = "Mgr. Business & Enterprise Service" = "Manager BES"
             * "Manager Project Operation & Quality Assurance" = "Mgr. Project Operation & Quality Assurance" = "Manager Proj. Op. & QA"
             * "General Manager" = "GM" = "Gen. Manager"
           - Case insensitive
           - Focus on MEANING, bukan exact text
        
        5. **Validate Mitra Penandatangan (SEMANTIC MATCHING):**
           - Same rules as Telkom
           - Common jabatan Mitra:
             * "Direktur" = "Director" = "Dir."
             * "Ketua" = "Chairman" = "Ketua Koperasi"
             * "Manager" = "Mgr." = "Manajer"
        
        6. **Validation Rules:**
           - Kedua penandatangan (Telkom & Mitra) harus ditemukan
           - Jika tidak ditemukan: catat sebagai missing
           - Compare JABATAN only (ignore NAMA)
           - Semantic matching untuk abbreviation
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi penandatangan BAUT (Telkom & Mitra)"
        - Keterangan:
          * **ALL PASS**: "Penandatangan dari Telkom dengan jabatan 'Manager Project Operation & Quality Assurance' sesuai dengan jabatan di Ground Truth. Penandatangan dari Mitra dengan jabatan 'Direktur' sesuai dengan jabatan di Ground Truth."
          
          * **ALL PASS (with abbreviation)**: "Penandatangan Telkom 'Mgr. Project Management' di BAUT match dengan 'Manager Project Management' di Ground Truth (abbreviation handled). Penandatangan Mitra 'Ketua' sesuai dengan Ground Truth."
          
          * **FAIL (Telkom)**: "Penandatangan Telkom dengan jabatan 'General Manager' di BAUT tidak match dengan jabatan 'Manager Project Operation & Quality Assurance' di Ground Truth (jabatan berbeda). Penandatangan Mitra sesuai dengan Ground Truth."
          
          * **FAIL (Mitra)**: "Penandatangan Telkom sesuai dengan Ground Truth. Penandatangan Mitra dengan jabatan 'Manager' di BAUT tidak match dengan jabatan 'Direktur' di Ground Truth (jabatan berbeda)."
          
          * **FAIL (both)**: "Penandatangan Telkom 'Senior Manager' tidak match dengan 'Manager Project Management' di Ground Truth. Penandatangan Mitra 'General Manager' tidak match dengan 'Direktur' di Ground Truth."
          
          * **FAIL (missing)**: "Penandatangan Telkom ditemukan dengan benar. Informasi penandatangan Mitra tidak ditemukan di dokumen BAUT (sudah dicek section PIHAK PERTAMA)."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 3: VALIDATE TABEL LAMPIRAN - DETAIL ITEM
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi tabel lampiran (detail item, periode, tanggal aktivasi, status)
        
        INSTRUKSI:
        
        1. **Locate Tabel Lampiran:**
           - Lokasi: Halaman 2 (biasanya dengan header "LAMPIRAN BERITA ACARA UJI TERIMA")
           - Pattern umum:
             ```
             | No | Detail Item | Jumlah | Satuan | Periode (Bulan) | No Order/SID/Tgl Aktif | Status |
             ```
        
        2. **Extract dari Tabel:**
           - Kolom yang perlu di-extract:
             * **No**: nomor urut item (1, 2, 3...)
             * **Detail Item**: nama pekerjaan/layanan
             * **Jumlah & Satuan**: "1 Orang", "1 Paket", dll
             * **Periode (Bulan)** atau **Masa Layanan (Bln)**: angka atau "N Bulan"
             * **No Order / SID / Tgl Aktif**: format "ORDER_NUM / SID_NUM / DD Bulan YYYY" atau "ORDER_NUM / SID_NUM / DD-MM-YYYY"
             * **Status**: "OK" atau nilai lain
        
        3. **Parse Periode/Masa Layanan:**
           - Extract numeric value:
             * "12 Bulan" → 12
             * "12" → 12
             * "11 Bulan" → 11
           - Handle variations: "12 bulan", "12 Bln", "12 bln"
           - Store as integer for comparison
        
        4. **Parse Tanggal Aktivasi:**
           - Extract dari kolom "Tgl Aktif" atau "Tgl Aktivasi"
           - Format bisa: "01 Februari 2025" atau "01-02-2025" atau "1 Feb 2025"
           - Standardize to DD-MM-YYYY for comparison
           - Month name mapping: Januari=01, Februari=02, ..., Desember=12
        
        5. **Count Items:**
           - Count jumlah baris item di tabel (exclude header)
           - Store as: baut_item_count
           - Note: Biasanya BAUT hanya punya 1 item, tapi bisa multiple
        
        6. **Validate Against Ground Truth:**
           
           **A. Detail Item:**
           - Compare extracted detail item dengan GT.judul_project
           - SEMANTIC MATCHING (keyword matching):
             * Case insensitive
             * Trim whitespace
             * Check keyword utama yang sama
             * Tidak harus exact 100%, threshold 60-70%
           - Contoh:
             * BAUT: "Tenaga TOS (Technician On Site)"
             * GT: "Pengadaan Tenaga TOS untuk Diskominfo Kukar"
             * Keywords match: Tenaga, TOS → PASS (keyword utama sama)
           
           **B. Periode/Masa Layanan:**
           - Parse extracted periode to integer
           - Parse GT.jangka_waktu.duration to integer (remove "Bulan")
           - Compare INTEGER values
           - Contoh:
             * BAUT: "12 Bulan" → 12
             * GT: "12 Bulan" → 12
             * Match → PASS
           
           **C. Tanggal Aktivasi:**
           - Parse extracted tanggal aktivasi ke DD-MM-YYYY
           - Parse GT.jangka_waktu.start_date ke DD-MM-YYYY
           - Compare date VALUES (ignore format)
           - Logika: Tanggal aktivasi seharusnya = start date kontrak
           - If mismatch: calculate difference in days
           
           **D. Status:**
           - Check apakah status = "OK"
           - Jika bukan "OK": catat nilai actual (e.g., "Pending", "Not OK", dll)
           - Note: GT tidak punya field status, jadi hanya validasi apakah = "OK" atau tidak
        
        7. **Handle Multiple Items:**
           - Jika ada multiple items:
             * Validate EACH item individually
             * ALL items harus punya periode yang SAMA = GT duration
             * ALL items harus punya tanggal aktivasi yang SAMA = GT start_date
             * ALL items harus punya status "OK"
           - Jika ada yang berbeda: mention detail item mana yang berbeda
        
        8. **Validation Rules:**
           - Tabel harus ditemukan di halaman 2
           - Minimal 1 item harus ada
           - Semua field (Detail Item, Periode, Tgl Aktivasi, Status) harus lengkap
           - Jika tidak ditemukan: catat sebagai missing dengan detail
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi tabel lampiran (detail item, periode, tanggal aktivasi, status)"
        - Keterangan:
          * **ALL PASS (single item)**: "Tabel lampiran berisi 1 item dengan detail 'Tenaga TOS (Technician On Site)' yang sesuai dengan judul project di Ground Truth (keyword match: Tenaga, TOS). Periode 12 bulan match dengan duration kontrak. Tanggal aktivasi 02 Januari 2024 sesuai dengan start date kontrak 02-01-2024. Status item 'OK'."
          
          * **ALL PASS (multiple items)**: "Tabel lampiran berisi 2 item. Semua item memiliki detail yang sesuai dengan judul project (keyword match). Periode 11 bulan untuk semua item match dengan duration kontrak. Tanggal aktivasi 01 Februari 2025 untuk semua item sesuai dengan start date kontrak. Semua item berstatus 'OK'."
          
          * **FAIL (detail item)**: "Tabel lampiran berisi 1 item dengan detail 'Penyediaan Admin untuk Polda' yang tidak sesuai dengan judul project 'Pengadaan Tenaga TOS untuk Diskominfo Kukar' (keyword berbeda: Admin vs TOS). Periode 12 bulan, tanggal aktivasi, dan status semuanya sesuai."
          
          * **FAIL (periode)**: "Detail item sesuai dengan judul project. Periode di tabel adalah 11 bulan sedangkan duration kontrak adalah 12 bulan (tidak match, selisih 1 bulan). Tanggal aktivasi dan status sesuai."
          
          * **FAIL (tanggal aktivasi)**: "Detail item dan periode sesuai. Tanggal aktivasi 05 Januari 2024 tidak match dengan start date kontrak 02 Januari 2024 (terlambat 3 hari). Status item 'OK'."
          
          * **FAIL (status)**: "Detail item, periode, dan tanggal aktivasi semuanya sesuai dengan Ground Truth. Namun status item adalah 'Pending' bukan 'OK'."
          
          * **FAIL (multiple - inconsistent)**: "Tabel lampiran berisi 2 item. Item 1 memiliki periode 12 bulan sesuai kontrak, sedangkan item 2 memiliki periode 11 bulan (tidak konsisten). Tanggal aktivasi item 1 sesuai, item 2 terlambat 3 hari. Item 1 status 'OK', item 2 status 'Pending'."
          
          * **FAIL (missing tabel)**: "Tabel lampiran tidak ditemukan di dokumen BAUT. Sudah dicek halaman 1 dan 2, tidak ada section 'LAMPIRAN BERITA ACARA UJI TERIMA' atau tabel detail item."
        
        ─────────────────────────────────────────────────────────────────────────
        NOTES & GUIDELINES
        ─────────────────────────────────────────────────────────────────────────
        
        PARSING DATES (do in thinking block):
        - Handle multiple formats:
          * "DD-MM-YYYY"
          * "DD Bulan YYYY"
          * "DD/MM/YYYY"
          * Terbilang: "Dua Puluh Satu Bulan Januari Tahun Dua Ribu Dua Puluh Lima"
        - Convert month names to numbers:
          * "Januari" → 01, "Februari" → 02, ..., "Desember" → 12
        - Standardize to DD-MM-YYYY for comparison
        - Example: "05 Juni 2023" → 05-06-2023
        
        PARSING DURATION (do in thinking block):
        - Extract number from text: "12 Bulan" → 12, "11 Bulan" → 11
        - Handle variations: "12 bulan", "12 Bln", "12 bln"
        - Store as integer
        - Ignore "Bulan" text for comparison
        
        SEMANTIC MATCHING FOR JABATAN (do in thinking block):
        - Abbreviation equivalents:
          * Manager = Mgr. = MGR = MANAGER = Manajer
          * General Manager = GM = Gen. Manager = General Mgr.
          * Project Manager = PM = Proj. Manager = Project Mgr.
          * Director = Direktur = Dir.
          * Chairman = Ketua
        - Always case insensitive
        - Focus on semantic meaning
        - Preserve full title: "Manager Project Operation & Quality Assurance"
        
        SEMANTIC MATCHING FOR JUDUL/DETAIL ITEM (do in thinking block):
        - Extract keywords from both BAUT and GT.judul_project
        - Common keywords: "Pengadaan", "Penyediaan", "Tenaga", "TOS", "Perangkat", nama instansi
        - Check keyword overlap
        - Threshold: at least 60-70% keyword match
        - Example:
          * BAUT: "Tenaga TOS (Technician On Site)"
          * GT: "Pengadaan Tenaga TOS untuk Diskominfo Kukar"
          * Keywords match: Tenaga, TOS
          * PASS (high keyword overlap)
        
        MULTI-PAGE HANDLING:
        - Halaman 1: Header + Identitas + Penandatangan
        - Halaman 2: Lampiran Tabel
        - Halaman 3+: Dokumentasi/Evident (SKIP validasi)
        - Extract from halaman 1-2 ONLY
        - If not found: state "sudah dicek halaman 1 dan 2"
        
        PENANDATANGAN IDENTIFICATION:
        - PIHAK PERTAMA = Mitra (Kopegtel, PT XXX, dll)
        - PIHAK KEDUA = Telkom (PT.TELKOM INDONESIA)
        - Pattern: "mewakili [NAMA ENTITAS]"
        - Extract: Nama + Jabatan dari section penandatangan
        
        TABEL LAMPIRAN COLUMNS:
        - Standard columns: No, Detail Item, Jumlah, Satuan, Periode (Bulan), No Order/SID/Tgl Aktif, Status
        - Variations possible: "Masa Layanan (Bln)", "Tgl Aktivasi"
        - Focus on: Detail Item, Periode, Tgl Aktif, Status
        
        STATUS VALIDATION:
        - Expected: "OK"
        - If not "OK": catat nilai actual
        - GT tidak punya field status, jadi hanya check apakah = "OK"
        
        ═════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT FORMAT (JSON ONLY)
        ═════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_header_identitas": {{
            "review": "Validasi header dan identitas dokumen BAUT",
            "keterangan": "[Narasi: Judul pekerjaan... Nomor kontrak... Tanggal BAUT...]"
          }},
          "stage_2_penandatangan": {{
            "review": "Validasi penandatangan BAUT (Telkom & Mitra)",
            "keterangan": "[Narasi: Penandatangan Telkom... Penandatangan Mitra...]"
          }},
          "stage_3_tabel_lampiran": {{
            "review": "Validasi tabel lampiran (detail item, periode, tanggal aktivasi, status)",
            "keterangan": "[Narasi: Detail item... Periode... Tanggal aktivasi... Status...]"
          }}
        }}
        
        ═════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES
        ═════════════════════════════════════════════════════════════════════════════
        
        ZERO HALLUCINATION:
        - Jika tidak ditemukan → catat sebagai missing
        - Jangan guess atau assume nilai
        - Jangan fabricate data
        
        VALUE-BASED COMPARISON (NOT FORMAT):
        - Jabatan: Semantic match, handle abbreviation
          * "Manager" = "Mgr." = "MGR" ✅
        - Nama: Ignore dalam comparison (only compare JABATAN)
        - Tanggal: Parse to date value, compare value
          * "05 Juni 2023" = "05-06-2023" ✅
        - Periode: Extract numeric, ignore "Bulan" text
          * "12 Bulan" = "12" ✅
        
        NARASI OUTPUT (STYLE):
        - NO "Ada yang salah" / "Sudah benar" di awal kalimat
        - Langsung narasi substansi untuk SETIAP aspek yang divalidasi
        - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning]"
        - Setiap aspek HARUS disebutkan (jangan skip)
        - Jika match: sebutkan nilai yang match
        - Jika tidak match: sebutkan kedua nilai dan perbedaan
        
        CONCISE BUT COMPLETE:
        - Keterangan maksimal 4-5 kalimat per stage
        - SETIAP aspek yang dicheck HARUS disebutkan hasilnya
        - Fokus pada HASIL dan INSIGHT
        - Jika multiple issues: prioritize yang paling critical
        
        COT IN THINKING BLOCK:
        - Semua reasoning, parsing, calculation di thinking block
        - Use the parsing and matching logic described in NOTES
        - Output hanya hasil akhir per aspek
        - No COT details in keterangan field
        
        MULTI-PAGE AWARENESS:
        - Extract from halaman 1-2 ONLY
        - Halaman 3+ (Dokumentasi) di-SKIP
        - If not found: explicitly state "sudah dicek halaman 1 dan 2"
        
        DATE FLEXIBILITY:
        - Handle multiple date formats as described in NOTES
        - Parse to standard format DD-MM-YYYY in thinking block
        - Compare date VALUES, not formats
        
        CONSISTENCY VALIDATION:
        - Cross-validate between stages
        - Stage 1 judul pekerjaan → should match Stage 3 detail item
        - Stage 1 tanggal BAUT → should be close to Stage 3 tanggal aktivasi (bisa beda 1-3 hari)
        - Stage 3 periode → should match GT.jangka_waktu.duration

        SKIP DOKUMENTASI/EVIDENT:
        - Halaman 3+ berisi foto, KTP, ijazah, SN perangkat, dll
        - NO validation untuk dokumentasi
        - Focus ONLY on halaman 1-2 (Header, Penandatangan, Tabel)
        
        RETURN FINAL JSON ANSWER ONLY. NO MARKDOWN, NO EXPLANATION, JUST JSON.
        """,
"P7":
        """
        P7 (PENETAPAN CALON MITRA PELAKSANA) VALIDATION PROMPT - 5 STAGE VERSION
        ═════════════════════════════════════════════════════════════════════════════
        Document Type: P7 (Penetapan Calon Mitra Pelaksana)
        Validation Mode: VALUE-BASED COMPARISON with SEMANTIC MATCHING
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output harus SINGKAT, JELAS, dan BERSAHABAT
        - FOKUS pada MAKNA/VALUE, bukan format literal
        - Handle OTC vs Bulanan dengan conditional logic di setiap stage
        - OUTPUT STYLE: Tanpa "Ada yang salah" / "Sudah benar", langsung narasi substansi
        
        ⚠️ SEMANTIC MATCHING RULES:
        - Angka: "21.252.000" = "21252000" = "Rp 21.252.000" (abaikan format, compare nilai)
        - Jabatan: "Manager" = "Mgr." = "MGR" = "MANAGER" (abaikan case & abbreviation)
        - Nama: "KOPEGTEL BALIKPAPAN" = "Kopegtel Balikpapan" (case insensitive)
        - Tanggal: "09-01-2025" = "9 Januari 2025" = "09 Jan 2025" (parse ke same date)
        - Judul: keyword matching, tidak harus exact 100%
        - Persentase: "99%" = "99" = "99 %" (extract numeric value)
        
        ⚠️ OUTPUT NARASI RULES:
        - Setiap aspek yang divalidasi HARUS dinarasikan hasilnya
        - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning singkat]"
        - Jika match: sebutkan nilai yang match
        - Jika tidak match: sebutkan kedua nilai dan selisih/perbedaan
        - NO "Sudah benar" / "Ada yang salah" di awal kalimat
        - Langsung ke substansi: "Nomor surat X sesuai dengan..." bukan "Sudah benar. Nomor surat..."
        
        ═════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ═════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        ═════════════════════════════════════════════════════════════════════════════
        P7 DOCUMENT TEXT (multi-page possible):
        ═════════════════════════════════════════════════════════════════════════════
        
        {p7_document_text}
        
        ═════════════════════════════════════════════════════════════════════════════
        TASK: Validate P7 Document dengan PRESISI TINGGI (Value-Based)
        ═════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: VALIDATE HEADER & IDENTITAS DOKUMEN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi informasi header di halaman pertama P7
        
        INSTRUKSI:
        
        1. **Extract Header Information:**
           - Lokasi: Bagian paling atas dokumen
           - Extract 4 field utama:
             * Nomor surat P7 (format: TEL.XXXX/LG.XXX/TRXX/YYYY)
             * Tanggal surat (format: "Balikpapan, DD Bulan YYYY" atau "Lokasi, DD-MM-YYYY")
             * Perihal/Judul pekerjaan (setelah "Perihal :")
             * Nama Mitra (setelah "Kepada Yth,")
        
        2. **Parse Tanggal P7:**
           - Format bisa: "Balikpapan, 09 Januari 2025" atau "Balikpapan, 09-01-2025"
           - Extract tanggal, parse ke format DD-MM-YYYY untuk comparison
           - Contoh: "09 Januari 2025" → 09-01-2025
        
        3. **Validate Against Ground Truth (VALUE-BASED):**
           
           **A. Nomor Surat:**
           - Compare extracted nomor dengan GT.nomor_surat_utama
           - EXACT match untuk nomor (case insensitive, trim whitespace)
           - Format umum: TEL.XXXX/LG.XXX/TRXX/YYYY
           - Contoh: "TEL.0329/LG.270/TR4-R401/2025"
           
           **B. Tanggal Surat:**
           - Parse extracted tanggal ke DD-MM-YYYY
           - Parse GT.tanggal_kontrak ke DD-MM-YYYY
           - Compare nilai tanggal (ignore format dan nama kota)
           - Contoh: "Balikpapan, 09 Januari 2025" = "09-01-2025"
           
           **C. Perihal/Judul Pekerjaan:**
           - Compare extracted perihal dengan GT.judul_project
           - SEMANTIC MATCHING:
             * Case insensitive
             * Trim whitespace
             * Check keyword matching (tidak harus exact 100%)
             * Perihal di P7 biasanya lebih panjang dari judul_project
             * Cukup keyword utama yang sama
           - Contoh: "Penetapan Calon Mitra Pelaksana Penyediaan Perangkat..." 
             MATCH dengan "Penyediaan Perangkat Pendukung Konektivitas"
             (keyword utama: Penyediaan, Perangkat)
           
           **D. Nama Mitra:**
           - Extract nama dari "Kepada Yth, [NAMA MITRA]"
           - Format bisa: "PT XXX" atau "Kopegtel XXX" atau nama lain
           - CATAT SAJA nama yang ditemukan (NO validation dengan GT.nama_pelanggan)
           - GT.nama_pelanggan adalah END CUSTOMER, berbeda dengan Mitra
           - Cukup extract dan mention dalam output
        
        4. **Validation Rules:**
           - Semua 4 field harus ditemukan di dokumen
           - Jika tidak ditemukan: catat sebagai missing dengan detail
           - Jika mismatch NILAI (bukan format): sebutkan perbedaan
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi header dan identitas dokumen P7"
        - Keterangan:
          * **ALL PASS**: "Nomor surat P7 'TEL.0329/LG.270/TR4-R401/2025' sesuai dengan nomor kontrak. Tanggal P7 09 Januari 2025 match dengan tanggal kontrak 09-01-2025. Perihal 'Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang' mengandung keyword utama yang sesuai dengan judul project. Mitra yang dituju adalah Kopegtel Balikpapan."
          
          * **FAIL (nomor)**: "Nomor surat P7 'TEL.XXXX/...' tidak sesuai dengan nomor kontrak 'TEL.YYYY/...'. Tanggal P7 09 Januari 2025 match dengan tanggal kontrak. Perihal sesuai dengan judul project. Mitra yang dituju adalah Kopegtel Balikpapan."
          
          * **FAIL (tanggal)**: "Nomor surat P7 sesuai dengan nomor kontrak. Tanggal P7 10 Januari 2025 tidak match dengan tanggal kontrak 09-01-2025 (selisih 1 hari). Perihal sesuai dengan judul project. Mitra yang dituju adalah Kopegtel Balikpapan."
          
          * **FAIL (perihal semantic)**: "Nomor surat dan tanggal P7 sesuai dengan kontrak. Perihal 'Penyediaan Admin untuk Polda' tidak mengandung keyword utama dari judul project 'Penyediaan Perangkat Pendukung Konektivitas' (keyword berbeda: Admin vs Perangkat). Mitra yang dituju adalah Kopegtel Balikpapan."
          
          * **FAIL (missing)**: "Nomor surat P7 tidak ditemukan di header dokumen. Tanggal, perihal, dan nama mitra ditemukan dengan benar."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 2: VALIDATE KETENTUAN KEUANGAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi ketentuan keuangan (harga, metode pembayaran, term of payment)
        
        INSTRUKSI:
        
        1. **Extract Ketentuan Keuangan:**
           - Lokasi: Bagian "dengan ketentuan sebagai berikut"
           - Extract field keuangan:
             * Total harga pekerjaan (poin 1 atau 3)
               - Pattern: "Jumlah harga Pekerjaan sebesar Rp.XXX"
               - Pattern: "Total harga pekerjaan sebesar Rp XXX"
               - Extract nilai numeric dan parse ke integer
             * Metode pembayaran (cari keyword: "Bulanan" atau "One Time Charge" atau "OTC")
             * Term of Payment / Tahapan Pembayaran (poin 4 atau 8)
               - Pattern: "Term of Payment / Tahapan Pembayaran ... [DETAIL]"
               - Extract text lengkap
             * Harga satuan (HANYA untuk metode Bulanan)
               - Pattern: "secara Bulanan sebesar Rp.XXX"
               - Extract nilai numeric dan parse ke integer
        
        2. **Parse Numeric Values (CRITICAL - IGNORE FORMAT):**
           - Input bisa: "21.252.000" atau "21252000" atau "Rp 21.252.000" atau "Rp. 21.252.000,-"
           - Process (lakukan di thinking block):
             * Remove: "Rp", "Rp.", ".", ",", "-", whitespace
             * Convert to integer: 21252000
           - Compare INTEGER VALUES only
        
        3. **Determine Payment Method (COT in thinking block):**
           - Search for keywords (case insensitive):
             * "Bulanan" → metode = "Bulanan"
             * "One Time Charge" atau "OTC" → metode = "OTC"
           - If both found: prioritize yang ada di "Term of Payment"
           - Store as: p7_metode_pembayaran
        
        4. **Validate Against Ground Truth (CONDITIONAL):**
           
           **A. Total Harga Pekerjaan:**
           - Parse extracted total to integer
           - Parse GT.dpp_raw to integer
           - Compare INTEGER values (ignore format)
           - Calculate difference if mismatch: abs(p7_total - GT.dpp_raw)
           - Calculate percentage: (difference / GT.dpp_raw) × 100
           
           **B. Metode Pembayaran:**
           - Compare: p7_metode_pembayaran vs GT.metode_pembayaran
           - Case insensitive
           - "One Time Charge" = "OTC"
           
           **C. Term of Payment:**
           - Extract full text dari P7
           - Compare dengan GT.terms_of_payment (semantic matching)
           - Check keyword utama:
             * "Back to Back" atau "Non Back to Back"
             * "Bulanan" atau "OTC"
             * Nama customer/pelanggan (optional)
           - Tidak harus exact 100%, cukup keyword match
           
           **D. Harga Satuan (CONDITIONAL - ONLY for Bulanan):**
           - IF GT.metode_pembayaran = "Bulanan":
             * Extract harga bulanan dari P7
             * Parse to integer
             * Compare dengan GT.harga_satuan_raw (integer)
             * Calculate difference if mismatch
           - IF GT.metode_pembayaran = "OTC":
             * Harga satuan diabaikan (tidak relevan untuk OTC)
             * SKIP validation ini
        
        5. **Validation Rules:**
           - Total harga HARUS match (exact atau within rounding tolerance ±1000)
           - Metode pembayaran HARUS match
           - Term of payment: semantic matching cukup
           - Harga satuan: hanya validasi jika metode = Bulanan
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi ketentuan keuangan (harga, metode pembayaran, term of payment)"
        - Keterangan:
          * **ALL PASS (Bulanan)**: "Total harga pekerjaan Rp 21.252.000 sesuai dengan DPP kontrak. Metode pembayaran 'Bulanan' match dengan Ground Truth. Term of payment 'secara Bulanan setelah TELKOM menerima pembayaran (Back to Back)' sesuai dengan ketentuan kontrak. Harga satuan bulanan Rp 1.932.000 match dengan harga satuan kontrak."
          
          * **ALL PASS (OTC)**: "Total harga pekerjaan Rp 76.902.240 sesuai dengan DPP kontrak. Metode pembayaran 'One Time Charge' match dengan Ground Truth. Term of payment 'Non Back to Back' sesuai dengan ketentuan kontrak."
          
          * **FAIL (total harga)**: "Total harga pekerjaan Rp 20.000.000 tidak sesuai dengan DPP kontrak Rp 21.252.000. Selisih: Rp 1.252.000 (5,89%). Metode pembayaran 'Bulanan' sesuai. Term of payment sesuai. Harga satuan bulanan sesuai."
          
          * **FAIL (metode)**: "Total harga pekerjaan sesuai dengan DPP kontrak. Metode pembayaran di P7 adalah 'OTC' sedangkan di kontrak 'Bulanan' (tidak match). Term of payment sesuai dengan kontrak OTC namun tidak konsisten dengan metode yang seharusnya Bulanan."
          
          * **FAIL (harga satuan Bulanan)**: "Total harga pekerjaan sesuai. Metode pembayaran 'Bulanan' sesuai. Term of payment sesuai. Namun harga satuan bulanan Rp 2.000.000 tidak match dengan harga satuan kontrak Rp 1.932.000. Selisih: Rp 68.000."
          
          * **PASS with rounding**: "Total harga pekerjaan Rp 21.252.500 sesuai dengan DPP kontrak Rp 21.252.000 dengan pembulatan kecil (selisih Rp 500). Metode pembayaran, term of payment, dan harga satuan bulanan semuanya sesuai."
          
          * **FAIL (missing)**: "Total harga pekerjaan tidak ditemukan di bagian ketentuan P7. Metode pembayaran dan term of payment ditemukan dengan benar."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 3: VALIDATE KETENTUAN TEKNIS & LAYANAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi ketentuan teknis (delivery, jangka waktu, lokasi, skema bisnis, SLG)
        
        INSTRUKSI:
        
        1. **Extract Ketentuan Teknis:**
           - Lokasi: Bagian "dengan ketentuan sebagai berikut" (lanjutan dari Stage 2)
           - Extract field teknis:
             * Target Delivery (poin 6 atau 7)
               - Pattern: "Target Delivery : DD Bulan YYYY" atau "Estimasi Target Delivery : DD-MM-YYYY"
               - Parse ke format date
             * Masa Kontrak Layanan / Jangka Waktu (poin 7 atau 8)
               - Pattern: "Masa Kontrak Layanan selama [DURATION] ([START] s.d [END])"
               - Pattern: "Estimasi Jangka Waktu kontrak : [DURATION] ([START] s.d [END])"
               - Extract: duration (e.g., "12 Bulan"), start_date, end_date
             * Lokasi Layanan / Lokasi Pekerjaan (poin 5 atau 6)
               - Pattern: "Lokasi Layanan: [NAMA]" atau "Lokasi Pekerjaan : [NAMA]"
               - Extract text lengkap
             * Skema bisnis (poin 9 atau 2)
               - Pattern: "Skema bisnis: [JENIS]" atau "Skema bisnis : [JENIS]"
               - Common values: "Sewa Murni", "Sewa Beli", "Jual Putus"
             * Ketentuan SLG (poin 10 atau 9)
               - Pattern: "Ketentuan SLG: [PERSENTASE]" atau "Ketentuan SLG : [PERSENTASE]"
               - Extract numeric value (ignore % symbol)
        
        2. **Parse Dates (COT in thinking block):**
           - Handle multiple formats:
             * "01 Februari 2025" → 01-02-2025
             * "01-02-2025" → 01-02-2025
             * "1 Feb 2025" → 01-02-2025
           - Standardize to DD-MM-YYYY for comparison
           - Month name mapping: Januari=01, Februari=02, ..., Desember=12
        
        3. **Parse Duration:**
           - Extract number from text: "12 Bulan" → 12, "11 Bulan" → 11
           - Store as integer for comparison
        
        4. **Parse SLG:**
           - Remove "%" symbol: "99%" → 99, "98%" → 98
           - Store as integer or string for comparison
        
        5. **Validate Against Ground Truth (VALUE-BASED):**
           
           **A. Target Delivery:**
           - Parse extracted delivery date to DD-MM-YYYY
           - Parse GT.delivery to DD-MM-YYYY
           - Compare date VALUES (ignore format)
           - If mismatch: calculate difference in days
           
           **B. Jangka Waktu:**
           - **Start Date:**
             * Compare extracted start_date vs GT.jangka_waktu.start_date
             * Date value comparison
           - **End Date:**
             * Compare extracted end_date vs GT.jangka_waktu.end_date
             * Date value comparison
           - **Duration:**
             * Compare extracted duration vs GT.jangka_waktu.duration
             * Numeric comparison (e.g., 12 vs 12)
           - ALL THREE must match
           
           **C. Lokasi Layanan:**
           - Compare extracted lokasi dengan GT.judul_project (SEMANTIC)
           - Lokasi biasanya nama customer/instansi yang ada di judul_project
           - Keyword matching, tidak harus exact
           - Contoh: Lokasi "DPMPTSP Kabupaten Bengkayang" 
             MATCH dengan judul "Penyediaan Perangkat... untuk DPMPTSP Kabupaten Bengkayang"
           
           **D. Skema Bisnis:**
           - Compare extracted skema dengan GT.skema_bisnis
           - Case insensitive
           - Exact match untuk jenis: "Sewa Murni" ≠ "Sewa Beli"
           
           **E. Ketentuan SLG:**
           - Compare extracted SLG value dengan GT.slg
           - Numeric comparison (ignore % format)
           - "99%" = "99" = "99 %"
        
        6. **Validation Rules:**
           - Semua 5 field harus ditemukan
           - Jika tidak ditemukan: catat sebagai missing
           - Dates: value comparison (ignore format)
           - Lokasi: semantic matching
           - Skema & SLG: value comparison
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi ketentuan teknis dan layanan (delivery, jangka waktu, lokasi, skema bisnis, SLG)"
        - Keterangan:
          * **ALL PASS**: "Target delivery 01 Februari 2025 sesuai dengan tanggal delivery kontrak. Jangka waktu kontrak 11 bulan (01 Februari 2025 s.d 31 Desember 2025) match dengan jangka waktu di Ground Truth untuk start date, end date, dan duration. Lokasi layanan 'DPMPTSP Kabupaten Bengkayang' sesuai dengan lokasi di judul project. Skema bisnis 'Sewa Murni' match dengan kontrak. Ketentuan SLG 99% sesuai dengan SLG kontrak."
          
          * **FAIL (delivery)**: "Target delivery 05 Februari 2025 tidak sesuai dengan tanggal delivery kontrak 01 Februari 2025 (terlambat 4 hari). Jangka waktu, lokasi, skema bisnis, dan SLG semuanya sesuai dengan kontrak."
          
          * **FAIL (jangka waktu - start)**: "Target delivery sesuai. Jangka waktu kontrak start date 01 Januari 2025 tidak match dengan start date kontrak 01 Februari 2025. End date dan duration sesuai. Lokasi, skema bisnis, dan SLG semuanya sesuai."
          
          * **FAIL (jangka waktu - duration)**: "Target delivery sesuai. Jangka waktu duration di P7 adalah 12 bulan sedangkan di kontrak 11 bulan (tidak match). Start date dan end date sesuai namun durasi tidak konsisten. Lokasi, skema bisnis, dan SLG semuanya sesuai."
          
          * **FAIL (lokasi semantic)**: "Target delivery dan jangka waktu sesuai. Lokasi layanan 'Polda Kalsel' tidak match dengan lokasi di judul project 'DPMPTSP Kabupaten Bengkayang' (lokasi berbeda). Skema bisnis dan SLG sesuai."
          
          * **FAIL (skema)**: "Target delivery, jangka waktu, dan lokasi semuanya sesuai. Skema bisnis di P7 adalah 'Sewa Beli' sedangkan di kontrak 'Sewa Murni' (tidak match). SLG sesuai."
          
          * **FAIL (SLG)**: "Target delivery, jangka waktu, lokasi, dan skema bisnis semuanya sesuai. Ketentuan SLG di P7 adalah 98% sedangkan di kontrak 99% (tidak match)."
          
          * **FAIL (multiple)**: "Target delivery 05 Februari 2025 terlambat 4 hari dari kontrak (01 Februari 2025). Jangka waktu duration 12 bulan tidak match dengan kontrak 11 bulan. Lokasi 'Polda Kalsel' berbeda dengan judul project 'DPMPTSP Bengkayang'. Skema bisnis dan SLG sesuai."
          
          * **FAIL (missing)**: "Target delivery, jangka waktu, skema bisnis, dan SLG ditemukan dengan benar. Namun lokasi layanan tidak ditemukan di bagian ketentuan P7."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 4: VALIDATE TABEL LAMPIRAN - DETAIL & PERHITUNGAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi tabel lampiran (detail item, periode, harga, total, grand total)
        
        INSTRUKSI:
        
        1. **Locate Tabel Lampiran:**
           - Cari section "Lampiran" atau header "Detail Rincian dan Spesifikasi"
           - Biasanya di halaman 2 atau 3
           - Ada 2 tabel:
             * Tabel 1: Detail Rincian (item-by-item)
             * Tabel 2: Rekap Harga (summary)
        
        2. **Extract dari Tabel 1 (Detail Rincian):**
           - Kolom yang perlu di-extract:
             * No (item number: 1, 2, 3...)
             * Detail Item (nama pekerjaan/layanan)
             * Jumlah & Satuan (1 Paket, 1 Orang, dll)
             * Periode / Masa Layanan (Bulan) - angka atau "N Bulan"
             * Harga Pekerjaan (IDR):
               - **Bulanan**: kolom "Bulanan"
               - **OTC**: kolom "OTC"
               - **Total**: kolom "Total" (per item)
        
        3. **Parse Numeric Values (CRITICAL):**
           - Remove all formatting: "Rp", ".", ",", spaces
           - Convert to integer
           - Examples:
             * "1.932.000" → 1932000
             * "Rp 21.252.000" → 21252000
             * "76.902.240" → 76902240
        
        4. **Count Items:**
           - Count jumlah baris item di tabel (exclude header & total)
           - Store as: p7_item_count
        
        5. **Validate Items (CONDITIONAL by Payment Method):**
           
           **IF GT.metode_pembayaran = "OTC":**
           - Expected: 1 item only
           - Check: p7_item_count = 1
           - If > 1: flag as issue (OTC seharusnya 1 item)
           
           **IF GT.metode_pembayaran = "Bulanan":**
           - Expected: bisa 1 atau multiple items
           - No restriction on item count
        
        6. **Validate Periode/Masa Layanan (for each item):**
           - Extract periode dari kolom "Periode (Bulan)" atau "Masa Layanan (Bln)"
           - Parse: "11 Bulan" → 11, "12" → 12
           - Compare dengan GT.jangka_waktu.duration (extract number)
           - ALL items harus punya periode yang sama = GT duration
           - IF GT.metode_pembayaran = "OTC": periode bisa diabaikan atau = GT duration
        
        7. **Validate Harga per Item (CONDITIONAL):**
           
           **IF GT.metode_pembayaran = "Bulanan":**
           - Extract "Harga Bulanan" dari kolom "Bulanan"
           - Parse to integer
           - Compare dengan GT.harga_satuan_raw
           - Tolerance: ±1000 (for rounding)
           - ALL items harus punya harga bulanan yang SAMA
           - Calculate "Total" expected: Harga Bulanan × Periode
           - Compare dengan actual "Total" di tabel
           
           **IF GT.metode_pembayaran = "OTC":**
           - Extract "OTC" dari kolom "OTC" atau "Total"
           - Parse to integer
           - Compare dengan GT.dpp_raw
           - Tolerance: ±1000
           - "Total" per item harus SAMA dengan nilai OTC
        
        8. **Extract GRAND TOTAL (dari Tabel 2 - Rekap Harga):**
           - Cari section "Rekap Harga" atau row "GRAND TOTAL" / "TOTAL (Sebelum PPN)"
           - Extract nilai numeric terbesar (biasanya di baris terakhir)
           - Parse to integer
           - Store as: p7_grand_total
        
        9. **Validate GRAND TOTAL (3 Methods):**
           
           **Method A: Compare dengan DPP**
           - Compare p7_grand_total vs GT.dpp_raw (integer)
           - Tolerance: ±1000 atau ±0.1%
           
           **Method B: Sum dari Items (Internal Consistency)**
           - Sum all "Total" values dari Tabel 1
           - Compare dengan p7_grand_total
           - Should be consistent
           
           **Method C: Calculate from Parameters**
           - **Bulanan**: Expected = GT.harga_satuan_raw × GT.duration × item_count
           - **OTC**: Expected = GT.dpp_raw
           - Compare dengan p7_grand_total
        
        10. **Validation Priority:**
            - Priority 1: Method A (DPP comparison) - MOST IMPORTANT
            - Priority 2: Method B (sum items) - internal consistency check
            - Priority 3: Method C (calculate from params) - secondary validation
        
        11. **Rounding Tolerance:**
            - Absolute: ±1000 (±Rp.1.000)
            - Percentage: ±0.1%
            - If within tolerance: flag as "pembulatan kecil, perlu ditinjau"
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi tabel lampiran (detail item, periode, harga, total, grand total)"
        - Keterangan:
          * **ALL PASS (Bulanan)**: "Tabel lampiran berisi 1 item dengan periode 11 bulan sesuai jangka waktu kontrak. Harga bulanan Rp 1.932.000 match dengan harga satuan kontrak. Perhitungan total per item: Rp 1.932.000 × 11 bulan = Rp 21.252.000 sudah benar. GRAND TOTAL di rekap harga Rp 21.252.000 sesuai dengan DPP kontrak dan konsisten dengan penjumlahan item."
          
          * **ALL PASS (OTC)**: "Tabel lampiran berisi 1 item untuk pembayaran OTC dengan periode 12 bulan. Nilai OTC Rp 76.902.240 match dengan DPP kontrak. Total per item sama dengan nilai OTC (Rp 76.902.240). GRAND TOTAL di rekap harga Rp 76.902.240 sesuai dengan DPP kontrak."
          
          * **PASS with rounding (Bulanan)**: "Tabel lampiran berisi 1 item dengan periode 11 bulan sesuai kontrak. Harga bulanan Rp 1.932.000 sesuai. Total per item Rp 21.252.500 dengan pembulatan kecil (ekspektasi Rp 1.932.000 × 11 = Rp 21.252.000, selisih Rp 500). GRAND TOTAL Rp 21.252.500 sesuai dengan total per item namun ada pembulatan kecil dari DPP kontrak Rp 21.252.000. Perlu ditinjau."
          
          * **FAIL (item count OTC)**: "Tabel lampiran berisi 3 item sedangkan untuk pembayaran OTC seharusnya hanya 1 item. Nilai total item pertama Rp 76.902.240 sesuai dengan DPP kontrak. GRAND TOTAL Rp 230.706.720 tidak sesuai karena duplikasi item (seharusnya Rp 76.902.240)."
          
          * **FAIL (periode)**: "Tabel lampiran berisi 1 item dengan periode 12 bulan sedangkan jangka waktu kontrak adalah 11 bulan (tidak match). Harga bulanan Rp 1.932.000 sesuai. Total per item Rp 23.184.000 (12 × Rp 1.932.000) tidak match dengan ekspektasi 11 bulan. GRAND TOTAL Rp 23.184.000 tidak sesuai dengan DPP kontrak Rp 21.252.000. Selisih: Rp 1.932.000 (1 bulan)."
          
          * **FAIL (harga bulanan)**: "Tabel lampiran berisi 1 item dengan periode 11 bulan sesuai kontrak. Harga bulanan Rp 2.000.000 tidak match dengan harga satuan kontrak Rp 1.932.000. Selisih: Rp 68.000. Total per item Rp 22.000.000 juga tidak sesuai. GRAND TOTAL Rp 22.000.000 tidak match dengan DPP kontrak Rp 21.252.000. Selisih: Rp 748.000."
          
          * **FAIL (calculation)**: "Tabel lampiran berisi 1 item dengan periode 11 bulan sesuai kontrak. Harga bulanan Rp 1.932.000 sesuai. Namun total per item di tabel Rp 20.000.000 tidak sesuai dengan perhitungan Rp 1.932.000 × 11 bulan = Rp 21.252.000. Selisih: Rp 1.252.000. GRAND TOTAL Rp 20.000.000 juga tidak match dengan DPP kontrak."
          
          * **FAIL (grand total only)**: "Tabel lampiran detail semuanya benar: 1 item, periode 11 bulan, harga bulanan Rp 1.932.000, total per item Rp 21.252.000. Namun GRAND TOTAL di rekap harga Rp 20.000.000 tidak konsisten dengan penjumlahan item Rp 21.252.000. Selisih: Rp 1.252.000. GRAND TOTAL juga tidak match dengan DPP kontrak."
          
          * **FAIL (OTC value)**: "Tabel lampiran berisi 1 item untuk OTC dengan periode 12 bulan sesuai kontrak. Nilai OTC di tabel Rp 70.000.000 tidak match dengan DPP kontrak Rp 76.902.240. Selisih: Rp 6.902.240 (9,86%). GRAND TOTAL Rp 70.000.000 juga tidak sesuai."
          
          * **FAIL (multiple items inconsistent)**: "Tabel lampiran berisi 2 item dengan periode 11 bulan sesuai kontrak. Item 1: harga bulanan Rp 1.932.000 sesuai. Item 2: harga bulanan Rp 2.000.000 tidak sesuai (seharusnya sama Rp 1.932.000). Total item 1 benar, total item 2 salah. GRAND TOTAL Rp 43.252.000 tidak match dengan DPP kontrak Rp 42.504.000 (2 × Rp 21.252.000)."
          
          * **FAIL (missing tabel)**: "Tabel lampiran tidak ditemukan di dokumen P7. Sudah dicek halaman 1, 2, dan 3. Tidak dapat melakukan validasi detail item, harga, dan grand total."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 5: VALIDATE PENANDATANGAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi penandatangan untuk 4 dokumen (BAST, BAUT, BARD, BAPL)
        
        INSTRUKSI:
        
        1. **Locate Penandatangan Section:**
           - Cari section dengan keyword:
             * "Bertindak sebagai penandatangan"
             * "Berita Acara Serah Terima (BAST)"
             * "Berita Acara Uji Terima (BAUT)"
             * "Berita Acara Rekonsiliasi Delivery (BARD)"
             * "Berita Acara Performansi Layanan (BAPL)"
           - Biasanya di halaman 1, sebelum atau setelah ketentuan
           - Format umum:
             ```
             Bertindak sebagai penandatangan BAST, BAUT, BARD, BAPL:
             . TELKOM: [JABATAN] atau pejabat pengganti yang ditunjuk
             . [NAMA MITRA]: [JABATAN] atau pejabat pengganti yang ditunjuk
             ```
        
        2. **Extract Penandatangan Information:**
           - Untuk masing-masing dokumen (BAST, BAUT, BARD, BAPL):
             * Penandatangan Telkom: extract jabatan
             * Penandatangan Mitra: extract jabatan
           - Format di P7 biasanya:
             * SEMUA 4 dokumen → penandatangan SAMA
             * "TELKOM: Mgr. Project Operation & Quality Assurance"
             * "[MITRA]: Direktur"
           - Ignore text "atau pejabat pengganti yang ditunjuk"
        
        3. **Parse Ground Truth Penandatangan:**
           - GT.pejabat_penanda_tangan format:
             ```json
             {{
               "bast": {{"telkom": "NAMA - JABATAN", "mitra": "NAMA - JABATAN"}},
               "baut": {{"telkom": "NAMA - JABATAN", "mitra": "NAMA - JABATAN"}},
               "bard": {{"telkom": "NAMA - JABATAN", "mitra": "NAMA - JABATAN"}},
               "bapl": {{"telkom": "NAMA - JABATAN", "mitra": "NAMA - JABATAN"}}
             }}
             ```
           - Split by " - " untuk get nama dan jabatan terpisah
           - HANYA compare JABATAN (nama diabaikan)
        
        4. **Validate Telkom Penandatangan (SEMANTIC MATCHING):**
           - For each dokumen (BAST, BAUT, BARD, BAPL):
             * Extract jabatan dari GT (split by " - ", ambil bagian kedua)
             * Compare dengan extracted jabatan dari P7
             * SEMANTIC MATCHING untuk abbreviation:
               - "Manager" = "Mgr." = "MGR" = "MANAGER" = "Manajer"
               - "General Manager" = "GM" = "Gen. Manager"
               - "Project Manager" = "PM" = "Proj. Manager"
               - "Assistant Manager" = "Asst. Manager" = "Ast. Mgr."
               - "Senior Manager" = "Sr. Manager" = "Senior Mgr."
               - "Manager Project Operation & Quality Assurance" = "Mgr. Project Operation & Quality Assurance" = "Manager Proj. Op. & QA"
             * Case insensitive
             * Focus on MEANING, bukan exact text
        
        5. **Validate Mitra Penandatangan (SEMANTIC MATCHING):**
           - Same rules as Telkom
           - Common jabatan Mitra: "Direktur", "Director", "General Manager", "Manager"
        
        6. **Check Consistency Across Documents:**
           - Di P7: biasanya SEMUA 4 dokumen punya penandatangan SAMA
           - Check apakah extracted Telkom jabatan SAMA untuk BAST = BAUT = BARD = BAPL
           - Check apakah extracted Mitra jabatan SAMA untuk BAST = BAUT = BARD = BAPL
           - IF berbeda antar dokumen di P7: flag as issue
        
        7. **Validate Against Ground Truth:**
           - For each dokumen (BAST, BAUT, BARD, BAPL):
             * Telkom: semantic match
             * Mitra: semantic match
           - Expected: GT mungkin punya jabatan berbeda per dokumen
           - Compare per dokumen basis
        
        8. **Special Case: Different Jabatan Across Documents in GT:**
           - IF GT.bast.telkom ≠ GT.baut.telkom (jabatan berbeda di GT):
             * P7 harus specify jabatan berbeda per dokumen
             * IF P7 specify SAMA untuk semua: potential issue
             * Flag dengan note: "P7 menyebutkan jabatan sama untuk semua dokumen, namun GT menunjukkan jabatan berbeda per dokumen. Perlu ditinjau apakah ini kesalahan P7 atau memang policy baru."
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi penandatangan untuk BAST, BAUT, BARD, dan BAPL"
        - Keterangan:
          * **ALL PASS (same for all docs)**: "Penandatangan dari Telkom untuk BAST, BAUT, BARD, dan BAPL adalah 'Manager Project Operation & Quality Assurance' sesuai dengan Ground Truth untuk keempat dokumen. Penandatangan dari Mitra untuk semua dokumen adalah 'Direktur' sesuai dengan Ground Truth untuk keempat dokumen."
          
          * **ALL PASS (with abbreviation)**: "Penandatangan Telkom 'Mgr. Project Operation & Quality Assurance' di P7 match dengan 'Manager Project Operation & Quality Assurance' di Ground Truth (abbreviation handled). Penandatangan Mitra 'Direktur' sesuai untuk semua dokumen (BAST, BAUT, BARD, BAPL)."
          
          * **FAIL (Telkom BAST)**: "Penandatangan Telkom untuk BAST di P7 adalah 'General Manager' sedangkan di Ground Truth 'Manager Project Operation & Quality Assurance' (tidak match). Penandatangan Telkom untuk BAUT, BARD, BAPL semuanya sesuai. Penandatangan Mitra untuk semua dokumen sesuai."
          
          * **FAIL (Mitra all docs)**: "Penandatangan Telkom untuk semua dokumen (BAST, BAUT, BARD, BAPL) sesuai dengan Ground Truth. Penandatangan Mitra di P7 adalah 'General Manager' sedangkan di Ground Truth 'Direktur' untuk semua dokumen (tidak match)."
          
          * **FAIL (multiple docs)**: "Penandatangan Telkom untuk BAST dan BAUT sesuai dengan Ground Truth. Namun untuk BARD, penandatangan Telkom di P7 adalah 'Senior Manager' sedangkan di GT 'Manager Project Management' (tidak match). Untuk BAPL juga tidak match. Penandatangan Mitra untuk semua dokumen sesuai."
          
          * **POTENTIAL ISSUE (P7 same, GT different)**: "P7 menyebutkan penandatangan Telkom sama untuk semua dokumen: 'Manager Project Operation'. Namun Ground Truth menunjukkan jabatan berbeda: BAST/BAUT menggunakan 'Manager Project Operation', sedangkan BARD/BAPL menggunakan 'Manager Project Management'. Perlu ditinjau apakah P7 perlu diupdate untuk reflect perbedaan ini atau memang policy baru menyamakan penandatangan."
          
          * **FAIL (inconsistent within P7)**: "P7 menyebutkan penandatangan berbeda per dokumen: BAST menggunakan 'Manager A', BAUT menggunakan 'Manager B'. Namun penandatangan untuk BAST di P7 ('Manager A') tidak match dengan GT ('Manager Project Operation'). Penandatangan BAUT, BARD, BAPL perlu dicek individual terhadap GT."
          
          * **FAIL (missing)**: "Informasi penandatangan untuk BAST, BAUT, BARD, dan BAPL tidak ditemukan di dokumen P7. Tidak dapat melakukan validasi penandatangan Telkom dan Mitra."
          
          * **PARTIAL (Telkom only found)**: "Penandatangan Telkom untuk BAST, BAUT, BARD, dan BAPL adalah 'Manager Project Operation & Quality Assurance' sesuai dengan Ground Truth. Namun informasi penandatangan Mitra tidak ditemukan di P7."
        
        ─────────────────────────────────────────────────────────────────────────
        NOTES & GUIDELINES
        ─────────────────────────────────────────────────────────────────────────
        
        **PARSING NUMERIC VALUES (do in thinking block):**
        - Remove all formatting characters: "Rp", "Rp.", ".", ",", "-", spaces
        - Convert to pure integer for comparison
        - Examples:
          * "21.252.000" → 21252000
          * "Rp 76.902.240" → 76902240
          * "1.932.000,-" → 1932000
        
        **PARSING DATES (do in thinking block):**
        - Handle multiple formats:
          * "DD-MM-YYYY"
          * "DD Bulan YYYY"
          * "DD/MM/YYYY"
        - Convert month names to numbers:
          * "Januari" → 01, "Februari" → 02, ..., "Desember" → 12
        - Standardize to DD-MM-YYYY for comparison
        - Example: "09 Januari 2025" → 09-01-2025
        
        **PARSING DURATION:**
        - Extract number from text: "12 Bulan" → 12, "11 Bulan" → 11
        - Handle variations: "12 bulan", "12 Bln", "12 bln"
        - Store as integer
        
        **PARSING PERCENTAGE:**
        - Remove "%" symbol: "99%" → 99
        - Store as string or integer
        - "99%" = "99" = "99 %" for comparison
        
        **SEMANTIC MATCHING FOR JABATAN (do in thinking block):**
        - Abbreviation equivalents:
          * Manager = Mgr. = MGR = MANAGER = Manajer
          * General Manager = GM = Gen. Manager = General Mgr.
          * Project Manager = PM = Proj. Manager = Project Mgr.
          * Assistant Manager = Asst. Manager = Ast. Mgr. = AM
          * Senior Manager = Sr. Manager = Senior Mgr. = SM
          * Director = Direktur = Dir.
        - Always case insensitive
        - Focus on semantic meaning
        - Preserve full title: "Manager Project Operation & Quality Assurance"
        
        **SEMANTIC MATCHING FOR JUDUL/PERIHAL (do in thinking block):**
        - Extract keywords from both P7 perihal and GT.judul_project
        - Common keywords: "Penyediaan", "Perangkat", "Layanan", "Admin", "Tenaga", nama instansi
        - Check keyword overlap
        - Threshold: at least 60-70% keyword match
        - Example:
          * P7: "Penetapan Calon Mitra Pelaksana Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang"
          * GT: "Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang"
          * Keywords match: Penyediaan, Perangkat, Pendukung, Konektivitas, DPMPTSP, Kabupaten, Bengkayang
          * PASS (high keyword overlap)
        
        **SEMANTIC MATCHING FOR LOKASI (do in thinking block):**
        - Compare lokasi layanan dengan judul_project
        - Lokasi biasanya nama instansi/customer yang ada di judul
        - Keyword matching cukup
        - Example:
          * Lokasi: "DPMPTSP Kabupaten Bengkayang"
          * Judul: "Penyediaan Perangkat... untuk DPMPTSP Kabupaten Bengkayang"
          * Keywords match: DPMPTSP, Kabupaten, Bengkayang
          * PASS
        
        **ROUNDING TOLERANCE LOGIC (do in thinking block):**
        - Calculate: difference = abs(actual - expected)
        - Calculate: percentage = (difference / expected) × 100 if expected > 0
        - If difference ≤ 1000: within tolerance
        - If percentage ≤ 0.1: within tolerance
        - If within tolerance: flag as "pembulatan kecil, perlu ditinjau"
        - Status: PASS with note
        
        **OTC vs BULANAN CONDITIONAL:**
        - OTC expectations:
          * Stage 2: NO harga satuan (skip validation)
          * Stage 4: 1 item only, OTC value = dpp_raw, Total = OTC
          * GRAND TOTAL = dpp_raw
        - Bulanan expectations:
          * Stage 2: harga satuan = harga_satuan_raw
          * Stage 4: Multiple items OK, Harga Bulanan = harga_satuan_raw
          * Total per item = Harga Bulanan × Periode
          * GRAND TOTAL = sum of all item totals = dpp_raw
        
        **MULTI-PAGE HANDLING:**
        - Halaman 1: Header + Ketentuan + Penandatangan
        - Halaman 2-3: Lampiran Tabel
        - Extract from ALL pages
        - If duplicate pages exist: extract from first occurrence only
        - If not found: state "sudah dicek halaman 1, 2, 3"
        
        **TERM OF PAYMENT KEYWORDS:**
        - "Back to Back" atau "B2B"
        - "Non Back to Back" atau "Non B2B"
        - "Bulanan" atau "Monthly"
        - "One Time Charge" atau "OTC"
        - "setelah TELKOM menerima pembayaran"
        - Nama customer/instansi
        
        **PENANDATANGAN CONSISTENCY:**
        - P7 biasanya list SEMUA 4 dokumen dengan penandatangan SAMA
        - GT mungkin punya penandatangan berbeda per dokumen
        - Check consistency:
          * Internal P7: apakah semua dok sama?
          * P7 vs GT: per dokumen basis
        - If GT berbeda tapi P7 sama: flag as potential issue
        
        ═════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT FORMAT (JSON ONLY)
        ═════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_header_identitas": {{
            "review": "Validasi header dan identitas dokumen P7",
            "keterangan": "[Narasi: Nomor surat... Tanggal... Perihal... Mitra...]"
          }},
          
          "stage_2_ketentuan_keuangan": {{
            "review": "Validasi ketentuan keuangan (harga, metode pembayaran, term of payment)",
            "keterangan": "[Narasi: Total harga... Metode pembayaran... Term of payment... Harga satuan (jika Bulanan)...]"
          }},
          
          "stage_3_ketentuan_teknis": {{
            "review": "Validasi ketentuan teknis dan layanan (delivery, jangka waktu, lokasi, skema bisnis, SLG)",
            "keterangan": "[Narasi: Target delivery... Jangka waktu (start, end, duration)... Lokasi... Skema bisnis... SLG...]"
          }},
          
          "stage_4_tabel_lampiran": {{
            "review": "Validasi tabel lampiran (detail item, periode, harga, total, grand total)",
            "keterangan": "[Narasi: Jumlah item... Periode... Harga bulanan/OTC... Total per item... GRAND TOTAL... Jika ada rounding, sebutkan]"
          }},
          
          "stage_5_penandatangan": {{
            "review": "Validasi penandatangan untuk BAST, BAUT, BARD, dan BAPL",
            "keterangan": "[Narasi: Penandatangan Telkom untuk BAST/BAUT/BARD/BAPL... Penandatangan Mitra... Jika ada perbedaan antar dokumen, sebutkan]"
          }}
        }}
        
        ═════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES
        ═════════════════════════════════════════════════════════════════════════════
        
        1. **ZERO HALLUCINATION:**
           - Jika tidak ditemukan → catat sebagai missing
           - Jangan guess atau assume nilai
           - Jangan fabricate data
        
        2. **VALUE-BASED COMPARISON (NOT FORMAT):**
           - Angka: Compare INTEGER value, ignore format
             * "21.252.000" = "21252000" ✅
             * Parse: remove Rp, dots, commas, spaces → integer
           - Jabatan: Semantic match, handle abbreviation
             * "Manager" = "Mgr." = "MGR" ✅
           - Nama: Case insensitive (not used in comparison, only extraction)
           - Tanggal: Parse to date value, compare value
             * "09 Januari 2025" = "09-01-2025" ✅
           - Persentase: Extract numeric, ignore %
             * "99%" = "99" ✅
        
        3. **NARASI OUTPUT (NEW STYLE):**
           - NO "Ada yang salah" / "Sudah benar" di awal kalimat
           - Langsung narasi substansi untuk SETIAP aspek yang divalidasi
           - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning]"
           - Setiap aspek HARUS disebutkan (jangan skip)
           - Jika match: sebutkan nilai yang match
           - Jika tidak match: sebutkan kedua nilai dan perbedaan
           - Contoh PASS: "Nomor surat P7 'TEL.XXX' sesuai dengan nomor kontrak. Tanggal P7 09-01-2025 match..."
           - Contoh FAIL: "Nomor surat P7 'TEL.XXX' tidak sesuai dengan nomor kontrak 'TEL.YYY'. Tanggal P7 match..."
        
        4. **CONCISE BUT COMPLETE:**
           - Keterangan maksimal 5-6 kalimat per stage
           - SETIAP aspek yang dicheck HARUS disebutkan hasilnya
           - Fokus pada HASIL dan INSIGHT
           - Jika multiple issues: prioritize yang paling critical
        
        5. **COT IN THINKING BLOCK:**
           - Semua reasoning, parsing, calculation di thinking block
           - Use the parsing and matching logic described in NOTES
           - Output hanya hasil akhir per aspek
           - No COT details in keterangan field
        
        6. **ROUNDING AWARENESS:**
           - Tolerance: ±Rp.1.000 atau ±0.1%
           - Jika ada rounding: mention "pembulatan kecil (selisih Rp XXX), perlu ditinjau"
           - Still describe as match/sesuai if within tolerance
           - Example: "Total Rp 21.252.500 sesuai dengan DPP kontrak Rp 21.252.000 dengan pembulatan kecil (selisih Rp 500)."
        
        7. **NUMERIC FORMAT IN OUTPUT:**
           - Display dengan thousand separator: "21.252.000"
           - Use "Rp" prefix: "Rp 21.252.000"
           - Sebutkan nilai konkrit dari dokumen, bukan placeholder
           - Show actual values: "Rp 21.252.000" not "Rp [NILAI]"
        
        8. **SEMANTIC MATCHING:**
           - Jabatan: Handle ALL abbreviations as described in NOTES
           - Judul/Perihal: Keyword matching, threshold 60-70%
           - Lokasi: Keyword matching dengan judul_project
           - Focus on MEANING, not literal text
        
        9. **OTC vs BULANAN CONDITIONAL:**
           - Every stage has conditional logic where applicable
           - Follow OTC vs BULANAN guidelines in NOTES
           - Stage 2: harga satuan only for Bulanan
           - Stage 4: different validation logic per method
        
        10. **MULTI-PAGE AWARENESS:**
            - Extract from ALL pages (1, 2, 3)
            - Follow multi-page handling guidelines in NOTES
            - If not found: explicitly state "sudah dicek halaman 1, 2, 3"
            - If duplicate: extract from first occurrence
        
        11. **DATE FLEXIBILITY:**
            - Handle multiple date formats as described in NOTES
            - Parse to standard format DD-MM-YYYY in thinking block
            - Compare date VALUES, not formats
            - Ignore city name in date: "Balikpapan, 09-01-2025" → focus on 09-01-2025
        
        12. **CONSISTENCY VALIDATION:**
            - Cross-validate between stages
            - Stage 2 metode pembayaran → affects Stage 4 validation logic
            - Stage 3 jangka waktu duration → affects Stage 4 periode validation
            - Stage 4 GRAND TOTAL → should match Stage 2 total harga pekerjaan
            - Internal consistency: sum of items should = GRAND TOTAL
        
        13. **PENANDATANGAN SPECIAL HANDLING:**
            - Validate ALL 4 documents: BAST, BAUT, BARD, BAPL
            - Expected: P7 usually same for all docs
            - GT might have different per doc
            - If P7 same but GT different: flag as potential issue
            - Semantic matching for jabatan (abbreviations)
            - Compare JABATAN only (ignore NAMA)
        
        14. **SKIP FIELDS NOT IN GT:**
            - NO validation for: Rujukan, Denda & Restitusi, Jaminan
            - NO comparison dengan GT.nama_pelanggan (beda dengan Mitra)
            - Focus ONLY on fields available in Ground Truth
        
        15. **ERROR PRIORITY:**
            - Critical: Nomor surat, Total harga, Metode pembayaran
            - High: Tanggal, Jangka waktu, GRAND TOTAL
            - Medium: Perihal (semantic), Lokasi (semantic), Penandatangan
            - Low: Rounding issues (within tolerance)
        
        RETURN FINAL JSON ANSWER ONLY. NO MARKDOWN, NO EXPLANATION, JUST JSON.
        """,
"TYPO":
        """
        You are a typo detection specialist for procurement documents. 
        Your task is to identify spelling errors with HIGH PRECISION (minimize false positives). 
        Use deterministic logic - same input must always produce same output.
        
        Dokumen Input yang akan diperiksa
        {document_text}

        ## TYPO DETECTION RULES
        
        ### PHASE 1: PRE-FILTERING (SKIP THESE)
        
        Before checking for typos, SKIP these patterns:
        
        **1. Identifiers & Codes**
        - Pattern: Contains "/" or "-" AND has >2 alphanumeric segments AND mixed letters+numbers
        - Examples: SDA0523-5242-TR6/KPGBJM/PTAT/AMEI23, K.TEL.0050/HK.810/TR6-R600/2024
        - Action: SKIP completely
        
        **2. Numbers & Currency**
        - Pattern: Digits with currency symbols, decimals, separators
        - Examples: Rp17.839.350, 123-0097021697, 5GB, 100Mbps, 00010, 00020
        - Action: SKIP completely
        
        **3. Dates**
        - Pattern: DD/MM/YYYY, DD-MM-YYYY, text date formats
        - Examples: 01-02-2025, 1 Januari 2025
        - Action: SKIP completely
        
        **4. Acronyms & Proper Nouns (Ambiguous)**
        - Pattern: ALL CAPS (2-5 chars), TitleCase institution names without clear error markers
        - Examples: BJM, KPGB, PT Telkom, Diskominfo, Kopegtel
        - Action: SKIP if low confidence (you cannot verify legitimacy)
        
        **5. Capitalization Variants**
        - Example: Manager vs manager vs MANAGER → ALL OK
        - Action: SKIP capitalization checks
        
        ### PHASE 2: TYPO DETECTION (7 TYPES)
        
        For each word that PASSED pre-filtering, check for these 7 typo types:
        
        **TYPE 1: Ejaan Dasar (Basic Spelling)**
        Check if word exists in KBBI (Bahasa Indonesia) or US English dictionary.
        
        Examples to detect:
        - kontark → kontrak (tidak ada di KBBI, closest match exists)
        - tagiahn → tagihan (not in dictionary, close match exists)
        - Region names inconsistency: Kutar vs Kukar (if Kukar appears 5+ times and Kutar only 1x → typo)
        
        Logic:
        - Is word in KBBI or English dictionary? → NOT a typo
        - Is word NOT in dictionary AND has close match (edit distance 1-2)? → TYPO
        - Is word a region/location name with low frequency vs others? → TYPO (consistency rule)
        
        **TYPE 2: Technical Terms (Bahasa Inggris)**
        Check US English spelling for technical/procurement terms.
        
        Examples to detect:
        - Guarentee → Guarantee
        - Availbality → Availability
        - Servicce → Service
        - Delivry → Delivery
        
        Logic:
        - Is word English technical term?
        - Check US English spelling (NOT UK)
        - If spelling wrong → TYPO
        
        **TYPE 3: Huruf Hilang (Missing Character)**
        Check if word becomes valid after inserting one missing character.
        
        Examples to detect:
        - pmbayaran → pembayaran
        - mnager → manager
        - tagihn → tagihan
        
        Logic:
        - Try inserting single character at each position
        - Does result match KBBI/English dictionary?
        - If yes → TYPO
        
        **TYPE 4: Huruf Dobel (Double Character)**
        Check if word becomes valid after removing duplicate consecutive characters.
        
        Examples to detect:
        - taggihan → tagihan
        - manajerr → manajer
        - servicce → service
        
        Logic:
        - Try removing consecutive duplicate chars
        - Does result match KBBI/English dictionary?
        - If yes → TYPO
        
        **TYPE 5: Transposisi (Character Swap)**
        Check if word becomes valid after swapping adjacent characters.
        
        Examples to detect:
        - tagiahn → tagihan
        - manaegr → manager
        - recieve → receive
        
        Logic:
        - Try swapping adjacent character pairs
        - Does result match KBBI/English dictionary?
        - If yes → TYPO
        
        **TYPE 6: Code-Switching Errors**
        Check spelling for words in mixed-language (Indonesian + English) context.
        
        Examples to detect:
        - Delivry siap digunakan → Delivery siap digunakan (English word in Indonesian sentence)
        - Pengirman sudah sampai → Pengiriman sudah sampai (Indonesian word)
        
        Logic:
        - Detect language of word (English = technical term, Indonesian = KBBI word)
        - Validate spelling according to language
        - If spelling wrong → TYPO
        
        **TYPE 7: Compound Word Errors (Kata Majemuk)**
        Check KBBI spacing rules for compound words.
        
        Common KBBI compound words (digabung, bukan terpisah):
        - pertanggung jawaban → pertanggungjawaban
        - pasca bayar → pascabayar
        - pra produksi → praproduksi
        - pra syarat → prasayarat
        
        Logic:
        - Is word a known KBBI compound word?
        - Is it currently separated with space?
        - If yes → TYPO (should be joined)
        
        ### PHASE 3: CONFIDENCE FILTERING
        
        Remove from output if:
        
        1. Proper noun without clear error: TitleCase, rare word, institution name → confidence too low
        2. Multiple possible corrections: If ambiguous → skip to minimize false positive
        3. Edit distance >3: Unlikely to be simple typo → skip
        
        Keep in output only:
        - Edit distance 1-2 AND dictionary match exists
        - OR strong consistency signal (name appears 5+ times one way, 1x different way)
        - OR obvious technical term misspelling (guarentee → guarantee)
        
        ## OUTPUT FORMAT
        
        Return ONLY valid JSON, no markdown, no explanations. Format:
        
        {{
          "typos": [
            {{
              "typo_word": "exact_word_from_document",
              "correction_word": "corrected_word_match_case",
              "page": 1
            }}
          ]
        }}
        
        Rules:
        - typo_word: Extract EXACTLY as appears in document (preserve original case)
        - correction_word: Corrected spelling, MATCH CASE with typo_word (if "Kontark" → "Kontrak", if "kontark" → "kontrak")
        - page: Integer from document page markers (<<<HALAMAN-X>>>)
        - Duplicate handling:
          - Same typo, different pages → list ALL with respective pages
          - Same typo, same page (multiple times) → list ONCE per page
        - Sorting: By page ascending, then by appearance order within page
        - No typos found: Return {{"typos": []}}
        
        ## CRITICAL INSTRUCTIONS
        
        DO:
        - Check EVERY word that passes pre-filtering
        - Use deterministic logic (same input = same output always)
        - Preserve exact case in typo_word
        - Match case in correction_word
        - List all instances across pages
        - Return valid JSON only
        
        DON'T:
        - Hallucinate or guess corrections
        - Flag capitalization differences as typos
        - Check numbers, dates, codes, identifiers
        - Skip any word without explicit rule
        - Change output format
        - Include markdown or explanations
        - Assume proper nouns are typos without evidence
        
        Extract page number from marker before analyzing content.
        """,
"SKM":
        """
        SKM (SURAT KESANGGUPAN MITRA) VALIDATION PROMPT - 3 STAGE VERSION
        ═════════════════════════════════════════════════════════════════════════════
        Document Type: SKM (Surat Kesanggupan Mitra)
        Validation Mode: VALUE-BASED COMPARISON with SEMANTIC MATCHING
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output harus SINGKAT, JELAS, dan BERSAHABAT
        - FOKUS pada MAKNA/VALUE, bukan format literal
        - OUTPUT STYLE: Tanpa "Ada yang salah" / "Sudah benar", langsung narasi substansi
        
        ⚠️ SEMANTIC MATCHING RULES:
        - Angka: "21.252.000" = "21252000" = "Rp 21.252.000" (abaikan format, compare nilai)
        - Jabatan: "Direktur" = "Dir." = "DIREKTUR" = "Director" (abaikan case & abbreviation)
        - Nama: "AFFANDHI ARIEF" = "Affandhi Arief" (case insensitive)
        - Judul: keyword matching, tidak harus exact 100%
        
        ⚠️ OUTPUT NARASI RULES:
        - Setiap aspek yang divalidasi HARUS dinarasikan hasilnya
        - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning singkat]"
        - Jika match: sebutkan nilai yang match
        - Jika tidak match: sebutkan kedua nilai dan selisih/perbedaan
        - NO "Sudah benar" / "Ada yang salah" di awal kalimat
        - Langsung ke substansi
        
        ⚠️ SKM CONTEXT:
        - SKM dibuat SEBELUM kontrak final
        - Nomor surat SKM dan tanggal SKM: EXTRACT saja (no validation)
        - Referensi nomor P7: EXTRACT saja (no cross-check)
        - Harga di SKM SELALU belum termasuk PPN (default assumption)
        - Lampiran tabel: optional (SKIP validation jika ada)
        
        ═════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ═════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        ═════════════════════════════════════════════════════════════════════════════
        SKM DOCUMENT TEXT (multi-page possible):
        ═════════════════════════════════════════════════════════════════════════════
        
        {skm_document_text}
        
        ═════════════════════════════════════════════════════════════════════════════
        TASK: Validate SKM Document dengan PRESISI TINGGI (Value-Based)
        ═════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: VALIDATE HEADER & IDENTITAS PENANDATANGAN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi perihal/judul dan identitas penandatangan SKM
        
        INSTRUKSI:
        
        1. **Extract Header Information (for context only):**
           - Lokasi: Bagian paling atas dokumen
           - Extract (NO validation, just for context):
             * Nomor surat SKM (format varies: 000712.3/SH-LL/R/XII/2023, 660/LG.000/GSD-560/2023, dll)
             * Tanggal SKM (format: "Jakarta, 20 Desember 2023" atau "Banjarmasin, 24 Mei 2023")
             * Kepada Yth (biasanya Manager Telkom)
             * Nama Perusahaan Mitra (dari header atau identitas section)
             * Alamat Perusahaan
        
        2. **Extract Perihal/Judul Pekerjaan:**
           - Lokasi: Field "Perihal :" di header
           - Atau dari "Menunjuk" section (referensi ke P7)
           - Atau dari body "menyatakan sanggup untuk melaksanakan [JUDUL]"
           - Prioritas ekstraksi:
             1. Perihal header (jika jelas dan lengkap)
             2. Body pernyataan kesanggupan
             3. Referensi Menunjuk (dari P7)
           - Extract full text judul pekerjaan
        
        3. **Extract Identitas Penandatangan:**
           - Lokasi: Section "Yang bertanda tangan di bawah ini:" atau signature section
           - Extract:
             * Nama (field "Nama :" atau dari signature)
             * Jabatan (field "Jabatan :" atau dari signature)
           - Format common:
             * "Nama : AFFANDHI ARIEF"
             * "Jabatan : Ketua"
           - Atau di signature: "AFFANDHI ARIEF / Ketua"
        
        4. **Validate Perihal/Judul (SEMANTIC MATCHING):**
           - Compare extracted perihal dengan GT.judul_project
           - SEMANTIC MATCHING:
             * Case insensitive
             * Trim whitespace
             * Check keyword matching (threshold 60-70%)
             * Perihal di SKM mungkin lebih panjang atau lebih pendek
             * Cukup keyword utama yang sama
           - Example:
             * SKM: "Kesanggupan Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang"
             * GT: "Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang"
             * Keywords match: Penyediaan, Perangkat, Pendukung, Konektivitas, DPMPTSP, Bengkayang
             * PASS
        
        5. **Parse GT Penandatangan Mitra (COT in thinking block):**
           - GT format: "NAMA - JABATAN" untuk each dokumen
           - Split by " - " untuk get nama dan jabatan terpisah
           - Check consistency across all 4 documents (bast, baut, bard, bapl):
             * Extract GT.pejabat_penanda_tangan.bast.mitra → split → nama1, jabatan1
             * Extract GT.pejabat_penanda_tangan.baut.mitra → split → nama2, jabatan2
             * Extract GT.pejabat_penanda_tangan.bard.mitra → split → nama3, jabatan3
             * Extract GT.pejabat_penanda_tangan.bapl.mitra → split → nama4, jabatan4
           - Check: IF nama1 = nama2 = nama3 = nama4 AND jabatan1 = jabatan2 = jabatan3 = jabatan4
             * Expected: SAMA untuk semua
             * Store as: gt_mitra_nama, gt_mitra_jabatan
        
        6. **Validate Penandatangan (SEMANTIC MATCHING):**
           
           **A. Nama:**
           - Compare: skm_nama vs gt_mitra_nama
           - Case insensitive
           - Trim whitespace
           - Allow minor typo (1-2 char difference)
           - Example: "AFFANDHI ARIEF" = "Affandhi Arief"
           
           **B. Jabatan:**
           - Compare: skm_jabatan vs gt_mitra_jabatan
           - SEMANTIC MATCHING untuk abbreviation:
             * "Direktur" = "Dir." = "DIREKTUR" = "Director"
             * "Manager" = "Mgr." = "MGR" = "MANAGER" = "Manajer"
             * "General Manager" = "GM" = "Gen. Manager"
             * "Ketua" = "KETUA" = "Chairman"
           - Case insensitive
           - Focus on MEANING
        
        7. **Handle Inconsistency in GT (edge case):**
           - IF GT.pejabat_penanda_tangan has different mitra across documents:
             * Flag this in output: "Ground Truth menunjukkan penandatangan mitra berbeda per dokumen: BAST/BAUT menggunakan [NAMA1 - JABATAN1], BARD/BAPL menggunakan [NAMA2 - JABATAN2]. SKM menggunakan [NAMA_SKM - JABATAN_SKM]. Perlu klarifikasi apakah SKM harus match dengan salah satu atau memerlukan update."
           - Expected: semua 4 dokumen di GT harus SAMA (as per your confirmation)
        
        8. **Validation Rules:**
           - Perihal harus match (semantic) dengan GT.judul_project
           - Nama penandatangan harus match dengan GT mitra
           - Jabatan penandatangan harus match (semantic) dengan GT mitra
           - Extract info (nomor, tanggal, perusahaan, alamat) untuk context saja
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi header dan identitas penandatangan SKM"
        - Keterangan:
          * **ALL PASS**: "Perihal SKM 'Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang' mengandung keyword utama yang sesuai dengan judul project. Penandatangan SKM adalah AFFANDHI ARIEF dengan jabatan Ketua, sesuai dengan penandatangan mitra di Ground Truth untuk semua dokumen (BAST, BAUT, BARD, BAPL). Nomor SKM 0091/UH.53/PENK/2025 tertanggal 10 Januari 2025 dari perusahaan KOPEGTEL Balikpapan."
          
          * **FAIL (perihal)**: "Perihal SKM 'Penyediaan Admin untuk Polda' tidak mengandung keyword utama dari judul project 'Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP' (keyword berbeda: Admin vs Perangkat). Penandatangan SKM adalah EKO BUDI SUSIANTO dengan jabatan GM Regional VI, sesuai dengan Ground Truth. Nomor SKM 660/LG.000/GSD-560/2023 tertanggal 6 September 2023 dari PT Graha Sarana Duta."
          
          * **FAIL (nama)**: "Perihal SKM sesuai dengan judul project. Penandatangan SKM adalah JOKO SUSILO dengan jabatan Manager, namun nama tidak match dengan Ground Truth (seharusnya AFFANDHI ARIEF). Jabatan Manager sesuai dengan Ground Truth. Nomor SKM dari KOPEGTEL BJM tertanggal 24 Mei 2023."
          
          * **FAIL (jabatan)**: "Perihal SKM sesuai dengan judul project. Penandatangan SKM adalah AFFANDHI ARIEF sesuai dengan Ground Truth, namun jabatan di SKM adalah 'General Manager' sedangkan di Ground Truth 'Ketua' (tidak match). Nomor SKM dari KOPEGTEL Balikpapan."
          
          * **FAIL (both nama & jabatan)**: "Perihal SKM sesuai dengan judul project. Penandatangan SKM adalah TJAHJADI dengan jabatan Direktur, tidak match dengan Ground Truth yang menyebutkan AFFANDHI ARIEF dengan jabatan Ketua. Nomor SKM 000712.3/SH-LL/R/XII/2023 dari PT Sumbersolusindo Hitech."
          
          * **FAIL (missing perihal)**: "Perihal tidak ditemukan di header SKM. Penandatangan SKM adalah AFFANDHI ARIEF dengan jabatan Ketua, sesuai dengan Ground Truth. Nomor SKM dari KOPEGTEL Balikpapan."
          
          * **FAIL (missing penandatangan)**: "Perihal SKM sesuai dengan judul project. Informasi penandatangan (nama dan jabatan) tidak ditemukan di dokumen SKM. Nomor SKM dari perusahaan mitra."
          
          * **EDGE CASE (GT inconsistent)**: "Perihal SKM sesuai dengan judul project. Ground Truth menunjukkan penandatangan mitra berbeda per dokumen: BAST/BAUT menggunakan AFFANDHI ARIEF - Ketua, BARD/BAPL menggunakan JOKO SUSILO - Manager. SKM menggunakan AFFANDHI ARIEF - Ketua. Perlu klarifikasi apakah SKM harus diupdate atau Ground Truth yang perlu koreksi."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 2: VALIDATE PERNYATAAN KESANGGUPAN - HARGA & STATUS PPN
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi harga total, terbilang, dan status PPN
        
        INSTRUKSI:
        
        1. **Extract Harga Total:**
           - Lokasi: Body pernyataan kesanggupan
           - Common patterns:
             * "dengan biaya sebesar Rp. 76.902.240,-"
             * "dengan harga Rp. 21.252.000,-"
             * "sebesar Rp 76.902.240,-"
           - Extract nilai numeric dan terbilang
           - Format: "Rp. [NUMERIC],- ([TERBILANG] rupiah)"
        
        2. **Extract Terbilang:**
           - Lokasi: Di dalam kurung setelah nilai numeric
           - Common patterns:
             * "(Tujuh puluh enam juta sembilan ratus dua ribu dua ratus empat puluh rupiah)"
             * "(Dua Puluh Satu Juta Dua Ratus Lima Puluh Dua Ribu Rupiah)"
           - Extract full text terbilang
           - Remove parentheses and "rupiah" suffix
        
        3. **Extract Status PPN:**
           - Lokasi: Kalimat setelah harga atau di bagian bawah
           - Common patterns:
             * "biaya/harga tersebut belum termasuk PPN"
             * "harga Belum termasuk Ppn"
             * "belum termasuk PPN 11%"
           - Search for keywords (case insensitive):
             * "belum termasuk PPN"
             * "belum termasuk Ppn"
             * "belum termasuk ppn"
             * "tidak termasuk PPN"
           - IF found: ppn_status = "belum termasuk PPN" (explicit)
           - IF NOT found: ppn_status = "belum termasuk PPN" (default - karena SKM selalu belum PPN)
        
        4. **Parse Numeric Value (CRITICAL - IGNORE FORMAT):**
           - Input bisa: "76.902.240" atau "76902240" atau "Rp. 76.902.240,-"
           - Process (lakukan di thinking block):
             * Remove: "Rp", "Rp.", ".", ",", "-", whitespace
             * Convert to integer: 76902240
           - Compare INTEGER VALUES only
        
        5. **Parse Terbilang to Numeric (Best Effort):**
           - Use Indonesian number word mapping:
             * Units: satu=1, dua=2, tiga=3, empat=4, lima=5, enam=6, tujuh=7, delapan=8, sembilan=9
             * Tens: sepuluh=10, dua puluh=20, tiga puluh=30, ..., sembilan puluh=90
             * Hundreds: seratus=100, dua ratus=200, ..., sembilan ratus=900
             * Thousands: seribu=1000, dua ribu=2000, ..., sembilan ribu=9000
             * Multipliers: ribu=×1000, juta=×1000000, miliar=×1000000000
           - Parse structure: [ratus juta] + [puluh juta] + [juta] + [ratus ribu] + ...
           - Handle "se-" prefix: seratus=100, seribu=1000, sejuta=1000000
           - Convert to integer: terbilang_numeric
           - IF parsing fails (complex format, OCR errors):
             * Return: "Tidak dapat parse terbilang untuk validasi otomatis"
             * Status: SKIP (not FAIL)
        
        6. **Validate Against Ground Truth:**
           
           **A. Harga Total (VALUE COMPARISON):**
           - Parse skm_harga_total to integer
           - Parse GT.dpp_raw to integer (atau GT.dpp, sama nilainya)
           - Compare INTEGER values (ignore format)
           - Tolerance: ±1000 (for rounding)
           - Calculate difference if mismatch: abs(skm_harga - GT.dpp_raw)
           - Calculate percentage: (difference / GT.dpp_raw) × 100
           
           **B. Terbilang Consistency (INTERNAL):**
           - Compare: terbilang_numeric vs skm_harga_total
           - This is INTERNAL consistency check (not GT validation)
           - IF match: consistent
           - IF NOT match: inconsistent (flag)
           - IF parse fails: SKIP (mention in output)
           
           **C. Status PPN (INFORMATIONAL):**
           - Report status PPN yang ditemukan atau default
           - Expected: "belum termasuk PPN" (default untuk SKM)
           - IF explicit found: mention positif
           - IF NOT found: mention "status PPN tidak disebutkan secara eksplisit, namun SKM umumnya belum termasuk PPN (default assumption)"
        
        7. **Rounding Tolerance:**
           - Absolute: ±1000 (±Rp.1.000)
           - Percentage: ±0.1%
           - If within tolerance: flag as "pembulatan kecil"
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi harga total, terbilang, dan status PPN"
        - Keterangan:
          * **ALL PASS (explicit PPN)**: "Harga total di SKM Rp 21.252.000 sesuai dengan DPP kontrak. Terbilang 'Dua Puluh Satu Juta Dua Ratus Lima Puluh Dua Ribu Rupiah' konsisten dengan nilai numeric. Status PPN disebutkan secara eksplisit: 'harga Belum termasuk Ppn'."
          
          * **ALL PASS (no explicit PPN, default)**: "Harga total di SKM Rp 76.902.240 sesuai dengan DPP kontrak. Terbilang 'Tujuh puluh enam juta sembilan ratus dua ribu dua ratus empat puluh rupiah' konsisten dengan nilai numeric. Status PPN tidak disebutkan secara eksplisit, namun SKM umumnya belum termasuk PPN (default assumption)."
          
          * **PASS with rounding**: "Harga total di SKM Rp 21.252.500 sesuai dengan DPP kontrak Rp 21.252.000 dengan pembulatan kecil (selisih Rp 500). Terbilang konsisten dengan nilai numeric SKM. Status PPN: belum termasuk PPN."
          
          * **FAIL (harga)**: "Harga total di SKM Rp 20.000.000 tidak sesuai dengan DPP kontrak Rp 21.252.000. Selisih: Rp 1.252.000 (5,89%). Terbilang 'Dua Puluh Juta Rupiah' konsisten dengan nilai numeric SKM (bukan GT). Status PPN: belum termasuk PPN."
          
          * **FAIL (terbilang)**: "Harga total di SKM Rp 21.252.000 sesuai dengan DPP kontrak. Namun terbilang 'Dua Puluh Juta Rupiah' (parsed: Rp 20.000.000) tidak konsisten dengan nilai numeric SKM Rp 21.252.000. Selisih: Rp 1.252.000. Status PPN: belum termasuk PPN."
          
          * **SKIP (terbilang parse fail)**: "Harga total di SKM Rp 21.252.000 sesuai dengan DPP kontrak. Terbilang tidak dapat di-parse untuk validasi otomatis (format complex atau OCR error), manual review recommended. Status PPN: belum termasuk PPN."
          
          * **FAIL (missing harga)**: "Harga total tidak ditemukan di pernyataan kesanggupan SKM. Tidak dapat melakukan validasi harga dan terbilang. Status PPN: belum termasuk PPN (disebutkan di dokumen)."
          
          * **FAIL (both harga & terbilang)**: "Harga total di SKM Rp 20.000.000 tidak match dengan DPP kontrak Rp 21.252.000 (selisih Rp 1.252.000). Terbilang 'Sembilan Belas Juta Rupiah' (parsed: Rp 19.000.000) juga tidak konsisten dengan nilai numeric SKM. Ada multiple inconsistencies."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 3: LAMPIRAN TABEL (OPTIONAL - INFORMATIONAL ONLY)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Check keberadaan lampiran tabel (NO validation, informational only)
        
        INSTRUKSI:
        
        1. **Check Lampiran Tabel:**
           - Cari di halaman 2 atau section "Lampiran"
           - Look for table dengan kolom:
             * Detail Item
             * Jumlah & Satuan
             * Masa Layanan (Bln) atau Periode (Bulan)
             * Harga Pekerjaan (Bulanan/OTC/Total)
             * GRAND TOTAL
           - IF found: lampiran_tabel = TRUE
           - IF NOT found: lampiran_tabel = FALSE
        
        2. **Report Status (NO VALIDATION):**
           - IF found: mention keberadaan tabel (informational)
           - IF NOT found: mention tidak ada tabel (acceptable)
           - NO validation terhadap isi tabel
           - NO comparison dengan GT
        
        3. **Acceptable Scenario:**
           - SKM BOLEH tidak punya lampiran tabel
           - SKM dengan tabel: acceptable
           - SKM tanpa tabel: acceptable
           - Both are valid scenarios
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Check lampiran tabel (informational)"
        - Keterangan:
          * **Tabel Found**: "Dokumen SKM ini memiliki lampiran tabel detail dengan rincian item, periode, harga bulanan, dan grand total. Tabel tidak divalidasi secara detail (informational only)."
          
          * **Tabel Not Found**: "Dokumen SKM ini tidak memiliki lampiran tabel detail. Ini acceptable untuk dokumen SKM (tabel bersifat optional)."
          
          * **Multi-page No Table**: "Dokumen SKM terdiri dari 2 halaman. Halaman 2 berisi informasi tambahan namun tidak ada tabel rinci. Ini acceptable untuk SKM."
        
        ─────────────────────────────────────────────────────────────────────────
        NOTES & GUIDELINES
        ─────────────────────────────────────────────────────────────────────────
        
        **PARSING NUMERIC VALUES (do in thinking block):**
        - Remove all formatting characters: "Rp", "Rp.", ".", ",", "-", spaces
        - Convert to pure integer for comparison
        - Examples:
          * "21.252.000" → 21252000
          * "Rp. 76.902.240,-" → 76902240
          * "76902240" → 76902240
        
        **PARSING TERBILANG (do in thinking block):**
        - Indonesian number word mapping (same as Kwitansi):
          * Units: satu=1, dua=2, ..., sembilan=9
          * Tens: sepuluh=10, dua puluh=20, ..., sembilan puluh=90
          * Hundreds: seratus=100, dua ratus=200, ..., sembilan ratus=900
          * Thousands: seribu=1000, dua ribu=2000, ..., sembilan ribu=9000
          * Multipliers: ribu=×1000, juta=×1000000, miliar=×1000000000
        - Handle "se-" prefix: seratus=100, seribu=1000, sejuta=1000000
        - Parse left-to-right, accumulate values
        - Example: "Dua Puluh Satu Juta Dua Ratus Lima Puluh Dua Ribu Rupiah"
          * "Dua Puluh Satu Juta" → 21000000
          * "Dua Ratus Lima Puluh Dua Ribu" → 252000
          * Total: 21000000 + 252000 = 21252000
        
        **SEMANTIC MATCHING FOR JABATAN (do in thinking block):**
        - Abbreviation equivalents:
          * Direktur = Dir. = DIREKTUR = Director
          * Manager = Mgr. = MGR = MANAGER = Manajer
          * General Manager = GM = Gen. Manager = General Mgr.
          * Ketua = KETUA = Chairman = Chairperson
        - Always case insensitive
        - Focus on semantic meaning
        
        **SEMANTIC MATCHING FOR JUDUL/PERIHAL (do in thinking block):**
        - Extract keywords from both SKM perihal and GT.judul_project
        - Common keywords: "Penyediaan", "Perangkat", "Layanan", "Admin", "Tenaga", nama instansi
        - Check keyword overlap
        - Threshold: at least 60-70% keyword match
        - Ignore filler words: "Surat Kesanggupan", "Penetapan", "untuk", "di", "pada"
        
        **ROUNDING TOLERANCE LOGIC (do in thinking block):**
        - Calculate: difference = abs(actual - expected)
        - Calculate: percentage = (difference / expected) × 100 if expected > 0
        - If difference ≤ 1000: within tolerance
        - If percentage ≤ 0.1: within tolerance
        - If within tolerance: flag as "pembulatan kecil"
        
        **SKM CONTEXT:**
        - SKM dibuat SEBELUM kontrak final
        - Harga SELALU belum termasuk PPN (default)
        - Nomor dan tanggal SKM: informational only (no validation)
        - Referensi P7: informational only (no cross-check)
        - Lampiran tabel: optional (no validation)
        
        **PENANDATANGAN CONSISTENCY:**
        - GT expected: semua 4 dokumen (bast/baut/bard/bapl) punya mitra SAMA
        - SKM harus match dengan mitra di GT
        - IF GT inconsistent (edge case): flag in output
        - Compare NAMA dan JABATAN (both must match)
        
        **TERBILANG PARSING (Best Effort):**
        - Try to parse terbilang to numeric
        - IF parsing succeeds: validate consistency
        - IF parsing fails: SKIP dengan mention "tidak dapat parse"
        - Don't FAIL just because parsing fails
        - This is internal consistency check, not critical validation
        
        **MULTI-PAGE HANDLING:**
        - Halaman 1: Header + Identitas + Pernyataan Kesanggupan
        - Halaman 2: Lampiran Tabel (optional)
        - Extract from ALL pages
        - If duplicate pages: extract from first occurrence
        - If not found: state "sudah dicek halaman 1, 2"
        
        **STATUS PPN HANDLING:**
        - Default assumption: "belum termasuk PPN" (karena SKM nature)
        - IF explicit found: report positif
        - IF NOT found: report default dengan note
        - NO warning atau fail jika tidak disebutkan
        - This is informational, not critical validation
        
        ═════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT FORMAT (JSON ONLY)
        ═════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_header_identitas": {{
            "review": "Validasi header dan identitas penandatangan SKM",
            "keterangan": "[Narasi: Perihal... Penandatangan (nama & jabatan)... Nomor SKM... Tanggal... Perusahaan...]"
          }},
          
          "stage_2_harga_ppn": {{
            "review": "Validasi harga total, terbilang, dan status PPN",
            "keterangan": "[Narasi: Harga total vs DPP... Terbilang consistency... Status PPN... Jika ada rounding, sebutkan]"
          }},
          
          "stage_3_lampiran_tabel": {{
            "review": "Check lampiran tabel (informational)",
            "keterangan": "[Narasi: Ada/tidak ada tabel... Informational only, tidak divalidasi detail]"
          }}
        }}
        
        ═════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES
        ═════════════════════════════════════════════════════════════════════════════
        
        1. **ZERO HALLUCINATION:**
           - Jika tidak ditemukan → catat sebagai missing
           - Jangan guess atau assume nilai
           - Jangan fabricate data
           - Hanya report apa yang benar-benar terlihat di dokumen
        
        2. **VALUE-BASED COMPARISON (bukan format-based):**
           - Numeric values: compare INTEGER only, ignore formatting
           - Remove semua karakter format: "Rp", ".", ",", "-", spaces
           - Example: "21.252.000" = "21252000" = "Rp. 21.252.000,-"
           - Tolerance: ±Rp 1.000 atau ±0.1% untuk rounding kecil
        
        3. **SEMANTIC MATCHING (bukan exact match):**
           - Nama: case-insensitive, minor typo OK (1-2 char)
           - Jabatan: abbreviation equivalent OK
             * "Direktur" = "Dir." = "DIREKTUR" = "Director"
             * "Manager" = "Mgr." = "MANAGER" = "Manajer"
             * "General Manager" = "GM" = "Gen. Manager"
             * "Ketua" = "KETUA" = "Chairman"
           - Judul/Perihal: keyword matching (threshold 60-70%)
           - Focus on MEANING, not literal string match
        
        4. **PENANDATANGAN CONSISTENCY:**
           - GT.pejabat_penanda_tangan expected: mitra SAMA untuk semua 4 dokumen
           - Parse "NAMA - JABATAN" format: split by " - "
           - Validate BOTH nama AND jabatan must match
           - IF GT inconsistent (different mitra per dokumen): flag as edge case
        
        5. **PPN HANDLING (SKM-specific):**
           - Default assumption: "belum termasuk PPN" (karena nature SKM)
           - Search for explicit indicator: "belum termasuk PPN", "tidak termasuk PPN"
           - IF explicit found: report positif dengan quote
           - IF NOT found: report default dengan note "SKM umumnya belum termasuk PPN"
           - NO warning atau fail jika tidak disebutkan eksplisit
           - Harga di SKM compare dengan GT.dpp_raw (bukan dpp_plus_ppn)
        
        6. **OUTPUT NARASI STYLE (NO "Sudah benar"/"Ada yang salah"):**
           - Langsung ke substansi tanpa prefix judgement
           - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning singkat]"
           - Setiap aspek yang divalidasi HARUS dinarasikan hasilnya
           - Jika match: sebutkan nilai yang match dan context
           - Jika tidak match: sebutkan kedua nilai, selisih/perbedaan, reasoning
           - Example CORRECT: "Harga total di SKM Rp 21.252.000 sesuai dengan DPP kontrak..."
           - Example WRONG: "Sudah benar. Harga total di SKM..."
        
        7. **TERBILANG VALIDATION (Best Effort - Internal Check):**
           - Parse terbilang to numeric menggunakan Indonesian number word mapping
           - Compare dengan nilai numeric di SKM (bukan GT)
           - This is INTERNAL consistency check
           - IF parsing succeeds: validate consistency dengan SKM numeric
           - IF parsing fails: SKIP dengan mention "tidak dapat parse untuk validasi otomatis"
           - Don't FAIL validation just because terbilang parsing fails
           - Manual review recommended jika parse fail
        
        8. **LAMPIRAN TABEL (Optional - Informational Only):**
           - Check keberadaan: ada/tidak ada tabel detail
           - NO validation terhadap isi tabel
           - NO comparison dengan GT
           - Report status: informational only
           - Both scenarios acceptable: SKM dengan tabel OK, SKM tanpa tabel OK
        
        9. **KEYWORD MATCHING FOR JUDUL/PERIHAL:**
           - Extract keywords dari SKM perihal dan GT.judul_project
           - Ignore filler words: "Surat Kesanggupan", "Penetapan", "untuk", "di", "pada", "yang"
           - Focus keywords: "Penyediaan", "Perangkat", "Layanan", "Admin", nama instansi, lokasi
           - Calculate keyword overlap percentage
           - Threshold: minimum 60-70% keyword match untuk PASS
           - Case insensitive comparison
           - Example:
             * SKM: "Kesanggupan Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang"
             * GT: "Penyediaan Perangkat Pendukung Konektivitas untuk DPMPTSP Kabupaten Bengkayang"
             * Keywords: Penyediaan, Perangkat, Pendukung, Konektivitas, DPMPTSP, Bengkayang (6/6 match) → PASS
        
        10. **ROUNDING TOLERANCE LOGIC:**
            - Absolute difference: ±Rp 1.000
            - Percentage difference: ±0.1%
            - IF within tolerance: flag as "pembulatan kecil" (not FAIL)
            - Mention both values dan selisih in output
            - Example: "Harga total di SKM Rp 21.252.500 sesuai dengan DPP kontrak Rp 21.252.000 dengan pembulatan kecil (selisih Rp 500)."
        
        11. **CHAIN OF THOUGHT (COT) IN THINKING BLOCK:**
            - All parsing logic: numeric, terbilang, semantic matching → dalam thinking block
            - All comparison logic dan calculation → dalam thinking block
            - Output keterangan: hanya hasil akhir dan insight
            - Jangan expose step-by-step parsing di keterangan
            - Keep keterangan concise (max 3-4 kalimat per stage)
        
        12. **MULTI-PAGE HANDLING:**
            - Halaman 1: Header + Identitas + Pernyataan Kesanggupan (always extract from here)
            - Halaman 2: Lampiran Tabel (optional, informational check only)
            - Extract dari ALL pages yang relevan
            - IF duplicate pages: extract from first occurrence
            - IF not found: explicitly state "sudah dicek halaman 1 dan 2"
        
        13. **INFORMATIONAL EXTRACTION (No Validation):**
            - Nomor surat SKM: extract untuk context (format varies)
            - Tanggal SKM: extract untuk context (format: "Jakarta, 20 Desember 2023")
            - Referensi nomor P7: extract jika ada (no cross-check)
            - Kepada Yth: extract jika ada
            - Nama perusahaan mitra: extract dari header atau identitas
            - Alamat perusahaan: extract jika ada
            - Semua ini: informational only, NO validation terhadap GT
        
        14. **NUMERIC FORMAT IN OUTPUT:**
            - Gunakan titik sebagai thousand separator: "21.252.000"
            - Gunakan prefix "Rp" atau "Rp." untuk mata uang
            - Sebutkan nilai konkrit, bukan placeholder
            - Example: "Rp 21.252.000" bukan "Rp [amount]"
            - Untuk selisih: "Rp 1.252.000 (5,89%)"
        
        15. **TERBILANG PARSING ALGORITHM (Indonesian Number Words):**
            - Units: satu=1, dua=2, tiga=3, empat=4, lima=5, enam=6, tujuh=7, delapan=8, sembilan=9
            - Teens: sepuluh=10, sebelas=11, dua belas=12, ..., sembilan belas=19
            - Tens: dua puluh=20, tiga puluh=30, ..., sembilan puluh=90
            - Hundreds: seratus=100, dua ratus=200, ..., sembilan ratus=900
            - Thousands: seribu=1000, dua ribu=2000, ..., sembilan ribu=9000
            - Multipliers: ribu=×1000, juta=×1000000, miliar=×1000000000
            - Handle "se-" prefix: seratus=100, seribu=1000, sejuta=1000000
            - Parse left-to-right, accumulate values with multipliers
            - Example: "Dua Puluh Satu Juta Dua Ratus Lima Puluh Dua Ribu"
              * Parse: 21 × 1000000 = 21000000
              * Parse: 252 × 1000 = 252000
              * Total: 21000000 + 252000 = 21252000
        
        16. **EDGE CASE: GT PENANDATANGAN INCONSISTENT:**
            - IF GT has different mitra per dokumen (e.g., BAST uses A, BARD uses B):
            - Flag this explicitly in output
            - Format: "Ground Truth menunjukkan penandatangan mitra berbeda per dokumen: BAST/BAUT menggunakan [NAMA1 - JABATAN1], BARD/BAPL menggunakan [NAMA2 - JABATAN2]. SKM menggunakan [NAMA_SKM - JABATAN_SKM]. Perlu klarifikasi apakah SKM harus match dengan salah satu atau memerlukan update GT."
            - Don't force match dengan salah satu
            - Request clarification in output
        
        17. **VALIDATION SEQUENCE:**
            - Execute stages in order: 1 → 2 → 3
            - Stage 1: Header & Identitas (critical)
            - Stage 2: Harga & PPN (critical)
            - Stage 3: Lampiran Tabel (informational)
            - IF Stage 1 fails: still continue to Stage 2 & 3
            - Don't short-circuit validation pipeline
            - Report all results regardless of early failures
        
        18. **CONTEXT PRESERVATION IN OUTPUT:**
            - Always mention document context: "di SKM", "pada SKM"
            - Include nomor dan tanggal SKM dalam narasi Stage 1
            - Include nama perusahaan mitra dalam narasi Stage 1
            - Provide full context untuk transparency
            - Example: "Nomor SKM 0091/UH.53/PENK/2025 tertanggal 10 Januari 2025 dari perusahaan KOPEGTEL Balikpapan."
        
        19. **JSON OUTPUT COMPLIANCE:**
            - NO text outside JSON structure
            - NO markdown code blocks (```json ... ```)
            - NO explanatory text before/after JSON
            - Valid JSON format: proper escaping, no trailing commas
            - All 3 stage keys must be present
            - Each stage must have "review" and "keterangan" fields
        
        20. **CONCISE KETERANGAN (Max 3-4 kalimat per stage):**
            - Stage 1: Perihal result + Penandatangan result + Context (nomor, tanggal, perusahaan)
            - Stage 2: Harga result + Terbilang result + Status PPN
            - Stage 3: Lampiran tabel status (ada/tidak ada) + Note informational only
            - Fokus pada insight, bukan process detail
            - Use clear, friendly language
            - Avoid repetition across stages
        
        21. **MISSING DATA HANDLING:**
            - Perihal missing: "Perihal tidak ditemukan di header SKM. [continue with other validations]"
            - Nama missing: "Informasi penandatangan (nama dan jabatan) tidak ditemukan di dokumen SKM."
            - Harga missing: "Harga total tidak ditemukan di pernyataan kesanggupan SKM. Tidak dapat melakukan validasi harga dan terbilang."
            - Don't stop validation jika satu field missing
            - Report missing field dan continue dengan field lain
        
        22. **FRIENDLY & PROFESSIONAL TONE:**
            - Avoid harsh language: "tidak sesuai" instead of "salah total"
            - Provide constructive feedback: mention selisih dan percentage
            - Use positive framing when pass: "sesuai dengan", "konsisten dengan"
            - Use neutral framing when fail: "tidak sesuai dengan", "tidak match dengan"
            - Maintain professional but friendly tone throughout
        
        23. **SKM NATURE & CONTEXT:**
            - SKM is pre-contract commitment letter
            - Created by mitra (vendor/partner)
            - Harga always refers to DPP (before PPN)
            - No need to validate contract reference (it's pre-contract)
            - Focus on: perihal match, penandatangan consistency, harga accuracy
            - Lampiran is optional enhancement (not required)
        
        24. **COMPARISON PRIORITY:**
            - Primary validation: Stage 1 (perihal & penandatangan) + Stage 2 (harga)
            - Secondary info: Stage 3 (lampiran tabel)
            - Critical failures: perihal mismatch, penandatangan mismatch, harga mismatch >5%
            - Acceptable issues: terbilang parse fail (SKIP), lampiran tidak ada, minor rounding
            - Report priority: critical → medium → informational
        
        25. **THINKING BLOCK USAGE:**
            - Use thinking block untuk semua COT reasoning
            - Parse numeric values dalam thinking block
            - Parse terbilang dalam thinking block
            - Calculate difference dan percentage dalam thinking block
            - Extract keywords untuk matching dalam thinking block
            - Determine semantic equivalence dalam thinking block
            - Output keterangan: only final results and insights
        
        ═════════════════════════════════════════════════════════════════════════════
        RETURN FINAL JSON ANSWER ONLY - NO ADDITIONAL TEXT
        ═════════════════════════════════════════════════════════════════════════════
        """,
"INVOICE":
        """
        INVOICE vs GROUND TRUTH VALIDATION PROMPT - UNIFIED ADAPTIVE VERSION
        ═════════════════════════════════════════════════════════════════════════════
        Document Type: INVOICE (Tagihan/Invoice)
        Validation Mode: STRICT DETERMINISTIC with ADAPTIVE STAGES (ZERO TOLERANCE)
        
        ⚠️ CRITICAL INSTRUCTION:
        - JANGAN HALLUCINATE data yang tidak ada di dokumen
        - JANGAN GUESS atau PREDICT nilai
        - Jika tidak ditemukan dengan PASTI → catat sebagai missing dalam keterangan
        - Gunakan Chain of Thought (COT) dalam thinking block saja
        - Output harus SINGKAT, JELAS, dan BERSAHABAT
        - ZERO TOLERANCE: semua nilai harus exact match (no rounding)
        - OUTPUT STYLE: Tanpa "Ada yang salah" / "Sudah benar", langsung narasi substansi
        - ADAPTIVE: Validate yang ada, SKIP yang tidak ada (unified approach)
        
        ⚠️ INVOICE CONTEXT:
        - Invoice adalah tagihan final untuk pembayaran
        - SEMUA invoice sudah include PPN (harga final)
        - PPN bisa 11% ATAU 12% (detect otomatis)
        - Format invoice sangat beragam (simple, detailed table, hybrid)
        - Duplicate pages: extract dari halaman 1 saja
        - Terbilang: WAJIB ada dan harus match dengan GRAND TOTAL (include PPN)
        
        ═════════════════════════════════════════════════════════════════════════════
        GROUND TRUTH
        ═════════════════════════════════════════════════════════════════════════════
        
        {ground_truth_json}
        
        Note:
        - GT.dpp_raw: DPP untuk full period (belum include PPN)
        - GT.harga_satuan_raw: DPP per bulan (belum include PPN)
        - Invoice SELALU sudah include PPN (GRAND TOTAL = DPP + PPN)
        
        ═════════════════════════════════════════════════════════════════════════════
        INVOICE DOCUMENT TEXT (multi-page possible, may contain duplicates):
        ═════════════════════════════════════════════════════════════════════════════
        
        {invoice_document_text}
        
        Format Variations:
        - Format 1 (Simple): Header + Total + PPN = GRAND TOTAL (no table)
        - Format 2 (Detailed Table): Header + Tabel per-periode + SUB TOTAL + PPN = GRAND TOTAL
        - Format 3 (Hybrid): Mix of simple and detailed
        
        Action: Extract from halaman 1 (first occurrence), ignore duplicate pages
        
        ═════════════════════════════════════════════════════════════════════════════
        TASK: Validate INVOICE Document dengan PRESISI TINGGI (Adaptive Unified)
        ═════════════════════════════════════════════════════════════════════════════
        
        STAGE 1: HEADER & REFERENSI KONTRAK
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi judul project dan referensi NOPES/Kontrak
        
        INSTRUKSI:
        
        1. **Extract Header Information (for context only - NO validation):**
           - Nomor invoice (extract saja, format varies)
           - Tanggal invoice (extract saja, no validation)
           - Bill To / Kepada Yth (biasanya PT. TELKOM)
           - Nama perusahaan mitra (dari header atau footer)
           - Store untuk context narasi
        
        2. **Extract Judul Project:**
           - Lokasi: Field "Project:", "Tentang:", "Nama Barang:", "Keterangan:", atau baris deskripsi item
           - Common patterns:
             * "Project : Penyediaan Admin untuk Polda Kalsel"
             * "Tentang : Penyediaan Perangkat Pendukung Konektivitas..."
             * "PENYEDIAAN TENAGA KERJA PARUH WAKTU UNTUK PT BUMI JAYA"
             * "Pengadaan EOS Dedicated Untuk Diskominfo Kabupaten Kutai Kartanegara"
             * Atau dari tabel: kolom "Keterangan" / "Nama Pekerjaan" / "Item Description"
           - Extract full text judul
           - Normalize: trim whitespace, case-insensitive
        
        3. **Extract Referensi NOPES/Kontrak:**
           - Lokasi: Field "No. Nota Pesanan:", "NO. NOPES:", "Sesuai dengan Kontrak Layanan No:", atau baris referensi
           - Common patterns:
             * "No. Nota Pesanan : K. TEL. 0812/HK.810/TR4-R400/2024"
             * "NO. NOPES : C.TEL.1940/YN.000/TR4-W300/2024"
             * "Sesuai dengan Kontrak Layanan No : K.TEL.0050/HK.810/TR6-R600/2024"
           - Extract nilai exact (preserve dots, slashes, spaces)
           - Format umum: [PREFIX].[NUMBER]/[CODE]/[YEAR]
        
        4. **Validate Judul Project (SEMANTIC MATCHING):**
           - Compare dengan GT.judul_project
           - SEMANTIC MATCHING:
             * Case insensitive
             * Trim whitespace
             * Check keyword matching (threshold 60-70%)
             * Judul di invoice mungkin lebih panjang atau lebih pendek
             * Cukup keyword utama yang sama
           - Example:
             * Invoice: "Penyediaan Admin untuk Polda Kalsel Periode 1 Oktober 2024 s.d 30 September 2024"
             * GT: "Penyediaan Admin untuk Polda Kalsel"
             * Keywords match: Penyediaan, Admin, Polda, Kalsel → PASS
        
        5. **Validate Referensi NOPES/Kontrak (EXACT MATCH):**
           - Compare dengan GT.nomor_surat_utama (primary)
           - EXACT match required:
             * Character-by-character comparison
             * Preserve format: dots, slashes, dashes, spaces
             * Case-sensitive (usually uppercase)
             * NO fuzzy match, NO tolerance
           - IF NOT match dengan nomor_surat_utama:
             * Try compare dengan GT.nomor_surat_lainnya (secondary)
           - IF both NOT match → FAIL
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi judul project dan referensi NOPES/Kontrak"
        - Keterangan:
          * **ALL PASS**: "Judul project 'Penyediaan Admin untuk Polda Kalsel' sesuai dengan Ground Truth. Referensi NOPES 'C.TEL.1940/YN.000/TR4-W300/2024' match dengan nomor kontrak. Invoice nomor 20209/KU370/GSD-200/2025 tertanggal 11 Maret 2025 dari PT. GRAHA SARANA DUTA."
          
          * **FAIL (judul)**: "Judul project 'Penyediaan Perangkat untuk Diskominfo' tidak mengandung keyword utama dari 'Penyediaan Admin untuk Polda Kalsel' (keyword berbeda: Perangkat vs Admin, Diskominfo vs Polda). Referensi NOPES sesuai. Invoice dari KOPEGTEL BJM."
          
          * **FAIL (referensi)**: "Judul project sesuai dengan Ground Truth. Referensi NOPES 'C.TEL.1940/YN.000/TR4-W300/2023' tidak match dengan Ground Truth 'C.TEL.1940/YN.000/TR4-W300/2024' (tahun berbeda: 2023 vs 2024). Invoice dari KOPEGTEL BJM."
          
          * **FAIL (both)**: "Judul project tidak sesuai (keyword mismatch). Referensi NOPES tidak match dengan Ground Truth. Invoice dari perusahaan mitra."
          
          * **FAIL (missing judul)**: "Judul project tidak ditemukan di invoice. Referensi NOPES sesuai dengan Ground Truth. Invoice dari perusahaan mitra."
          
          * **FAIL (missing referensi)**: "Judul project sesuai dengan Ground Truth. Referensi NOPES/Kontrak tidak ditemukan di invoice. Invoice dari perusahaan mitra."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 2: PERIODE COVERAGE
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Determine periode coverage (full/partial/single month)
        
        INSTRUKSI:
        
        1. **Extract Periode from Invoice:**
           - Lokasi: Field "Periode:", baris deskripsi, atau dari tabel detail
           - Common patterns:
             * "Periode 1 Oktober 2024 s.d 30 September 2024" (range)
             * "Periode Februari 2025 s/d Juni 2025" (range)
             * "JUNI 2023 sd MEI 2024" (range)
             * "Periode Januari 2024" (single month)
             * Atau dari tabel: kolom "Periode (Bulanan)" dengan multiple rows
           
           **A. Single Periode (Simple Format):**
           - Extract start month + year
           - Extract end month + year (if range)
           - IF range → parse both
           - IF single month → start = end
           
           **B. Multiple Periode (Table Format):**
           - Count jumlah baris dalam tabel
           - Extract periode dari each row:
             * "Periode Januari 2024", "Periode Februari 2024", ...
           - Determine: start = earliest month, end = latest month
           - Count: total_bulan = jumlah baris
        
        2. **Calculate Coverage (COT in thinking block):**
           - Count jumlah bulan dari start ke end
           - Handle year transition (e.g., Nov 2024 - Feb 2025 = 4 bulan)
           - Handle full year (e.g., Juni 2023 - Mei 2024 = 12 bulan)
           - Store as: invoice_periode_bulan (integer)
        
        3. **Extract Expected from Ground Truth:**
           - Parse GT.jangka_waktu.start_date → extract month + year
           - Parse GT.jangka_waktu.end_date → extract month + year
           - Parse GT.jangka_waktu.duration → extract number (e.g., "12 Bulan" → 12)
        
        4. **Validate Period Match:**
           - Compare start month + year (Invoice vs GT)
           - Compare end month + year (Invoice vs GT)
           - Compare calculated duration (Invoice vs GT)
           - Note: Cukup match bulan + tahun (ignore tanggal spesifik)
        
        5. **Determine Coverage Type:**
           - IF invoice_periode_bulan == GT.duration → **FULL PERIOD**
           - IF invoice_periode_bulan < GT.duration → **PARTIAL PERIOD**
           - IF invoice_periode_bulan == 1 → **SINGLE MONTH**
           - Store coverage_type untuk use in Stage 3
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi periode coverage"
        - Keterangan:
          * **PASS (full)**: "Periode di invoice 'Januari 2024 s.d Desember 2024' (12 bulan) match dengan jangka waktu kontrak (full period coverage)."
          
          * **PASS (partial)**: "Periode di invoice 'Februari 2025 s.d Juni 2025' (5 bulan) adalah partial coverage dari jangka waktu kontrak (12 bulan total)."
          
          * **PASS (single)**: "Periode di invoice 'Februari 2025' (1 bulan) adalah single month coverage dari jangka waktu kontrak."
          
          * **FAIL (start)**: "Start periode di invoice 'Februari 2024' tidak match dengan kontrak 'Januari 2024'."
          
          * **FAIL (end)**: "End periode di invoice 'Juli 2025' tidak match dengan expected 'Juni 2025' untuk coverage 5 bulan."
          
          * **FAIL (duration)**: "Durasi periode di invoice (8 bulan) tidak konsisten dengan start 'Januari 2024' dan end 'Agustus 2024' yang seharusnya 8 bulan, namun tidak match dengan jangka waktu kontrak (12 bulan)."
          
          * **FAIL (not found)**: "Periode tidak ditemukan di invoice. Tidak dapat menentukan coverage type."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 3: TOTAL AMOUNT VALIDATION (with PPN AUTO-DETECTION & DYNAMIC CALC)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi DPP + PPN = GRAND TOTAL (dengan PPN 11% atau 12%)
        
        INSTRUKSI:
        
        1. **Extract Amounts from Invoice:**
           
           **Common Labels:**
           - DPP/SUB TOTAL/HARGA JUAL: "SUB TOTAL", "Harga Jual", "HARGA", "Nilai Tagihan" (before PPN)
           - PPN: "PPN", "Ppn", "PPn"
           - GRAND TOTAL: "GRAND TOTAL", "Total Invoice", "Nilai Tagihan" (after PPN)
           
           **A. Simple Format (no explicit DPP):**
           - Extract: "New Charge" atau "Nilai Tagihan" (ini biasanya DPP)
           - Extract: "PPN" (explicit PPN amount)
           - Calculate: GRAND TOTAL = Nilai Tagihan + PPN
           
           **B. Detailed Format (explicit SUB TOTAL):**
           - Extract: "SUB TOTAL" atau "Harga Jual" (DPP)
           - Extract: "PPN" (explicit PPN amount)
           - Extract: "GRAND TOTAL" atau "Total Invoice"
           
           **C. Alternative Format:**
           - IF hanya ada 1 nilai besar → assume ini GRAND TOTAL (include PPN)
           - Back-calculate DPP: DPP = GRAND_TOTAL / 1.11 atau / 1.12
        
        2. **Parse Numeric Values (CRITICAL - IGNORE FORMAT):**
           - Remove all formatting: "Rp", "Rp.", ".", ",", "-", spaces
           - Convert to integer
           - Store:
             * invoice_dpp (integer)
             * invoice_ppn (integer)
             * invoice_grand_total (integer)
        
        3. **Detect PPN Rate (11% or 12%):**
           
           **Method 1: From Explicit PPN Amount**
           - IF PPN amount tersedia:
             * Calculate rate: (invoice_ppn / invoice_dpp) × 100
             * IF rate ≈ 11% (±0.1%) → ppn_rate = 11%
             * IF rate ≈ 12% (±0.1%) → ppn_rate = 12%
             * IF neither → UNKNOWN (flag as issue)
           
           **Method 2: From GRAND TOTAL Back-Calculation**
           - IF only GRAND TOTAL tersedia:
             * Try ppn_rate = 11%: calculate DPP = GRAND_TOTAL / 1.11
             * Try ppn_rate = 12%: calculate DPP = GRAND_TOTAL / 1.12
             * Compare with expected DPP (from GT)
             * Choose ppn_rate yang menghasilkan DPP closest to expected
           
           **Default Logic:**
           - First try 11%
           - IF not match → try 12%
           - IF both not match → report both attempts in output
        
        4. **Determine Expected DPP (COT in thinking block):**
           
           **Use results from Stage 2:**
           - coverage_type: FULL, PARTIAL, atau SINGLE
           - invoice_periode_bulan: integer (jumlah bulan)
           
           **Logic:**
           
           **IF coverage_type == FULL PERIOD:**
           - Expected DPP = GT.dpp_raw
           
           **IF coverage_type == PARTIAL PERIOD:**
           - Expected DPP = GT.harga_satuan_raw × invoice_periode_bulan
           
           **IF coverage_type == SINGLE MONTH:**
           - Expected DPP = GT.harga_satuan_raw
        
        5. **Validate DPP (EXACT MATCH):**
           - Compare: invoice_dpp vs expected_dpp
           - ZERO TOLERANCE: exact match required
           - IF match → DPP PASS
           - IF NOT match:
             * Calculate difference: abs(invoice_dpp - expected_dpp)
             * Calculate percentage: (difference / expected_dpp) × 100
             * Report both values and difference
        
        6. **Validate PPN Calculation (EXACT MATCH):**
           - Expected PPN = invoice_dpp × (ppn_rate / 100)
           - Round to nearest integer (karena PPN biasanya integer)
           - Compare: invoice_ppn vs expected_ppn
           - ZERO TOLERANCE: exact match required
           - IF match → PPN PASS
           - IF NOT match: report difference
        
        7. **Validate GRAND TOTAL (EXACT MATCH):**
           - Expected GRAND TOTAL = invoice_dpp + invoice_ppn
           - Compare: invoice_grand_total vs expected_grand_total
           - ZERO TOLERANCE: exact match required
           - IF match → GRAND TOTAL PASS
           - IF NOT match: report difference
        
        8. **Provide Context in Output:**
           - Mention: coverage type (full/partial/single)
           - Mention: PPN rate detected (11% or 12%)
           - Mention: calculation method used
           - IF PPN rate auto-detected: explicitly state "PPN 11% detected" atau "PPN 12% detected"
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi DPP, PPN, dan GRAND TOTAL"
        - Keterangan:
          * **ALL PASS (11%, full)**: "DPP Rp 76.902.240 sesuai dengan GT.dpp_raw (full period coverage). PPN 11% sebesar Rp 8.459.246 correct (11% dari DPP). GRAND TOTAL Rp 85.361.486 sesuai dengan DPP + PPN."
          
          * **ALL PASS (12%, partial)**: "DPP Rp 9.660.000 sesuai dengan perhitungan GT.harga_satuan_raw (Rp 1.932.000) × 5 bulan (partial coverage). PPN 12% sebesar Rp 1.159.200 correct (12% dari DPP). GRAND TOTAL Rp 10.819.200 sesuai dengan DPP + PPN."
          
          * **ALL PASS (single month)**: "DPP Rp 1.932.000 sesuai dengan GT.harga_satuan_raw (single month coverage). PPN 11% sebesar Rp 212.520 correct. GRAND TOTAL Rp 2.144.520 sesuai dengan DPP + PPN."
          
          * **FAIL (DPP)**: "DPP di invoice Rp 75.000.000 tidak sesuai dengan GT.dpp_raw Rp 76.902.240 (full period). Selisih: Rp 1.902.240 (2,47%). PPN 11% calculation based on invoice DPP correct (Rp 8.250.000). GRAND TOTAL Rp 83.250.000."
          
          * **FAIL (PPN calculation)**: "DPP Rp 76.902.240 sesuai dengan Ground Truth. Namun PPN di invoice Rp 8.000.000 tidak correct, seharusnya Rp 8.459.246 (11% dari DPP). Selisih PPN: Rp 459.246. GRAND TOTAL Rp 84.902.240 juga tidak sesuai."
          
          * **FAIL (GRAND TOTAL)**: "DPP dan PPN calculation correct. Namun GRAND TOTAL di invoice Rp 85.500.000 tidak match dengan expected Rp 85.361.486 (DPP + PPN). Selisih: Rp 138.514."
          
          * **FAIL (PPN rate unknown)**: "DPP Rp 76.902.240 sesuai dengan Ground Truth. Namun PPN rate tidak dapat dideteksi: PPN di invoice Rp 9.000.000 tidak match dengan 11% (expected Rp 8.459.246) maupun 12% (expected Rp 9.228.269). Perlu klarifikasi PPN rate yang digunakan."
          
          * **FAIL (multiple issues)**: "DPP di invoice Rp 75.000.000 tidak sesuai dengan GT Rp 76.902.240 (selisih Rp 1.902.240). PPN Rp 8.000.000 juga tidak correct untuk 11% maupun 12%. GRAND TOTAL Rp 83.000.000 tidak sesuai. Multiple inconsistencies detected."
          
          * **FAIL (missing amounts)**: "SUB TOTAL/DPP tidak ditemukan di invoice. Hanya GRAND TOTAL Rp 85.361.486 tersedia. Tidak dapat validasi breakdown DPP dan PPN."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 4: TERBILANG CONSISTENCY CHECK (WAJIB)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi consistency terbilang dengan GRAND TOTAL (include PPN)
        
        INSTRUKSI:
        
        1. **Extract Terbilang:**
           - Lokasi: Field "Terbilang:", baris dengan text terbilang, atau dalam "#...#" markers
           - Common patterns:
             * "Terbilang : # delapan puluh lima juta ... #"
             * "Terbilang : Dua ratus tiga puluh tujuh juta ..."
             * "(Dua Puluh Satu Juta ... Rupiah)"
           - Extract full text in Indonesian
           - Remove: "#" markers, parentheses, "Rupiah" suffix
           - Store as: terbilang_text (string)
        
        2. **Parse Terbilang to Numeric (Mandatory):**
           - Use Indonesian number word mapping:
             * Units: satu=1, dua=2, tiga=3, ..., sembilan=9
             * Teens: sepuluh=10, sebelas=11, ..., sembilan belas=19
             * Tens: dua puluh=20, tiga puluh=30, ..., sembilan puluh=90
             * Hundreds: seratus=100, dua ratus=200, ..., sembilan ratus=900
             * Thousands: seribu=1000, dua ribu=2000, ..., sembilan ribu=9000
             * Multipliers: ribu=×1000, juta=×1000000, miliar=×1000000000
           - Handle "se-" prefix: seratus=100, seribu=1000, sejuta=1000000
           - Parse left-to-right, accumulate values
           - Convert to integer: terbilang_numeric
           
           **IF parsing fails:**
           - Report: "Terbilang tidak dapat di-parse untuk validasi otomatis (format complex atau OCR error)"
           - Status: FAIL (karena terbilang WAJIB dan harus bisa di-parse)
        
        3. **Compare with GRAND TOTAL:**
           - Get invoice_grand_total from Stage 3
           - Compare: terbilang_numeric vs invoice_grand_total
           - ZERO TOLERANCE: exact match required
           - IF match → consistent
           - IF NOT match → inconsistent (FAIL)
        
        4. **Terbilang is MANDATORY:**
           - IF terbilang tidak ditemukan di invoice → FAIL
           - IF terbilang found but cannot parse → FAIL
           - IF terbilang parsed but not match → FAIL
           - Only PASS if: found + parsed + match
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi consistency terbilang dengan GRAND TOTAL"
        - Keterangan:
          * **PASS**: "Terbilang 'delapan puluh lima juta tiga ratus enam puluh satu ribu empat ratus delapan puluh enam rupiah' konsisten dengan GRAND TOTAL Rp 85.361.486."
          
          * **FAIL (not match)**: "Terbilang 'delapan puluh juta rupiah' (parsed: Rp 80.000.000) tidak konsisten dengan GRAND TOTAL Rp 85.361.486. Selisih: Rp 5.361.486."
          
          * **FAIL (cannot parse)**: "Terbilang ditemukan namun tidak dapat di-parse untuk validasi otomatis (format complex atau OCR error). Manual review required. Text: '# delapan puluh lima juta tiga ratus [unclear] #'."
          
          * **FAIL (not found)**: "Terbilang tidak ditemukan di invoice. Terbilang adalah field mandatory untuk invoice validation."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 5: DETAIL REKENING (OPTIONAL - ADAPTIVE)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi detail rekening bank (jika ada di invoice)
        
        INSTRUKSI:
        
        1. **Check Availability:**
           - Cari section detail rekening di invoice
           - Common locations: footer, payment instruction section
           - Common labels: "Behalf of", "A/C", "Bank", "Cabang", "Atas Nama"
           
           **IF detail rekening NOT FOUND:**
           - Return: "Detail rekening tidak tersedia di invoice (optional field)."
           - Status: SKIP (not FAIL)
           - End Stage 5
        
        2. **Extract Detail Rekening (IF AVAILABLE):**
           
           **A. NAMA BANK:**
           - Pattern: "Bank [NAMA]", "[NAMA] Bank", sebelum nomor rekening
           - Example: "Mandiri", "BCA", "Bank Mandiri"
           - Extract: nama bank
           
           **B. KANTOR CABANG:**
           - Pattern: "Cabang [NAMA]", "[NAMA] Branch", "KK [NAMA]"
           - Example: "Cabang Jakarta Cut Meutia", "KK Telkom Divre VI Kalimantan"
           - Extract: nama cabang
           
           **C. NOMOR REKENING:**
           - Pattern: "A/C : [NOMOR]", "No. : [NOMOR]", "IDR : [NOMOR]"
           - Format variations: "123-0097021697", "149-000700-5806", "1490008000772"
           - Extract exact format (preserve dashes, dots, spaces)
           
           **D. ATAS NAMA:**
           - Pattern: "Behalf of [NAMA]", "Atas Nama [NAMA]", "a/n [NAMA]"
           - May include: "PT.", "CV", "Koperasi", etc.
           - Extract: complete account holder name
        
        3. **Validate Against Ground Truth:**
           
           **IF GT.detail_rekening is NULL:**
           - Return: "Detail rekening ditemukan di invoice, namun tidak ada data di Ground Truth untuk comparison."
           - Status: SKIP
           
           **IF GT.detail_rekening is available:**
           
           **A. Nama Bank:**
           - Compare: invoice_bank vs GT.detail_rekening.nama_bank
           - Case-insensitive match
           - Fuzzy match OK (≥80% similarity)
           - Example: "MANDIRI" = "Mandiri" = "Bank Mandiri"
           
           **B. Kantor Cabang:**
           - Compare: invoice_cabang vs GT.detail_rekening.kantor_cabang
           - Case-insensitive match
           - Fuzzy match OK (≥80% similarity)
           - Example: "Jakarta Cut Meutia" = "JAKARTA CUT MEUTIA"
           
           **C. Nomor Rekening:**
           - Compare: invoice_rekening vs GT.detail_rekening.nomor_rekening
           - EXACT format match required (no fuzzy)
           - Preserve dashes, dots, spaces
           - Character-by-character comparison
           
           **D. Atas Nama:**
           - Compare: invoice_atas_nama vs GT.detail_rekening.atas_nama
           - Case-insensitive match
           - Fuzzy match OK (≥90% similarity)
           - Example: "PT SUMBERSOLUSINDO HITECH" = "PT. Sumbersolusindo Hitech"
        
        4. **Aggregate Validation:**
           - ALL 4 fields must match untuk PASS
           - Track which fields pass and which fail
           - IF multiple failures: list all
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi detail rekening bank (optional, adaptive)"
        - Keterangan:
          * **SKIP (not found)**: "Detail rekening tidak tersedia di invoice (optional field)."
          
          * **SKIP (no GT data)**: "Detail rekening ditemukan di invoice: Bank Mandiri Cabang Jakarta Cut Meutia, A/C 123-0097021697 a/n PT SUMBERSOLUSINDO HITECH. Namun tidak ada data di Ground Truth untuk comparison."
          
          * **ALL PASS**: "Detail rekening sesuai: Bank Mandiri Cabang Jakarta Cut Meutia, No. Rekening 123-0097021697, Atas Nama PT SUMBERSOLUSINDO HITECH (semua field match dengan Ground Truth)."
          
          * **FAIL (single field)**: "Detail rekening: Bank Mandiri dan Cabang sesuai, Nomor Rekening di invoice '123-0097021697' tidak match dengan GT '149-0097021697', Atas Nama sesuai."
          
          * **FAIL (multiple fields)**: "Detail rekening: Bank sesuai. Namun 3 field tidak match: (1) Cabang di invoice 'Jakarta' vs GT 'Jakarta Cut Meutia', (2) Nomor Rekening '123-0097021697' vs '149-0097021697', (3) Atas Nama 'PT SSH' vs 'PT SUMBERSOLUSINDO HITECH'."
          
          * **FAIL (incomplete)**: "Detail rekening tidak lengkap di invoice: hanya Bank dan Nomor Rekening ditemukan, Cabang dan Atas Nama tidak tersedia."
        
        ─────────────────────────────────────────────────────────────────────────
        STAGE 6: TABEL DETAIL VALIDATION (OPTIONAL - ADAPTIVE, Format 2 only)
        ─────────────────────────────────────────────────────────────────────────
        
        OBJECTIVE: Validasi per-period breakdown dalam tabel (jika ada)
        
        INSTRUKSI:
        
        1. **Check Availability:**
           - Cari tabel detail dengan kolom:
             * No
             * Keterangan / Nama Pekerjaan / Item Description
             * Jumlah / Qty
             * Satuan / Unit
             * Periode (Bulanan) / Masa Sewa (Bulan)
             * Bulanan / Unit Price / Harga/Unit
             * Total Harga (Rp) / Amount
           
           **IF tabel NOT FOUND:**
           - Return: "Tabel detail tidak tersedia di invoice (Format 1 atau Format 3). Validation focus pada total amounts only."
           - Status: SKIP (not FAIL)
           - End Stage 6
        
        2. **Extract Tabel Data (IF AVAILABLE):**
           
           **For each row in table:**
           - Extract: No (row number)
           - Extract: Periode (e.g., "Periode Januari 2024", "Juni 2023")
           - Extract: Harga Bulanan (per-period amount, before PPN)
           - Extract: Total (same as Harga Bulanan jika Jumlah = 1)
           
           **Count rows:**
           - Total rows = jumlah periode dalam tabel
           - Store as: table_row_count
        
        3. **Validate Row Count vs Coverage:**
           - Get invoice_periode_bulan from Stage 2
           - Compare: table_row_count vs invoice_periode_bulan
           - They should match (jumlah baris = jumlah periode)
           - IF NOT match: flag as inconsistency
        
        4. **Validate Harga Bulanan (per row):**
           - Extract harga bulanan dari each row
           - Expected: GT.harga_satuan_raw (DPP per bulan, before PPN)
           - Compare: each row's harga vs GT.harga_satuan_raw
           - ZERO TOLERANCE: exact match required
           
           **Check consistency:**
           - ALL rows should have SAME harga bulanan
           - IF different values across rows: flag as inconsistency
           - IF harga not match GT: flag which rows are different
        
        5. **Validate SUB TOTAL Calculation:**
           - Calculate: expected_sub_total = GT.harga_satuan_raw × table_row_count
           - Extract: invoice SUB TOTAL from summary section
           - Compare: invoice_sub_total vs expected_sub_total
           - ZERO TOLERANCE: exact match required
           - This should also match with invoice_dpp from Stage 3
        
        6. **Validate Periode Sequence (optional consistency check):**
           - Check: periode dalam tabel sequential? (Jan, Feb, Mar, ... atau gaps?)
           - IF gaps (e.g., Jan, Mar, May): flag as unusual (not FAIL, just note)
           - IF out of order (e.g., Feb, Jan, Mar): flag as inconsistency
        
        EXPECTED OUTPUT STYLE (Narasi Substansi):
        - Review: "Validasi tabel detail per-periode (optional, adaptive)"
        - Keterangan:
          * **SKIP (no table)**: "Tabel detail tidak tersedia di invoice (Format 1 simple). Validation focus pada total amounts only."
          
          * **ALL PASS**: "Tabel detail dengan 12 baris (Januari 2024 - Desember 2024) sesuai dengan periode coverage. Harga bulanan Rp 17.839.350 per baris consistent dan match dengan GT.harga_satuan_raw. SUB TOTAL Rp 214.072.200 sesuai dengan perhitungan (Rp 17.839.350 × 12)."
          
          * **ALL PASS (partial)**: "Tabel detail dengan 5 baris (Februari 2025 - Juni 2025) sesuai dengan partial coverage. Harga bulanan Rp 1.932.000 per baris consistent dan match dengan GT.harga_satuan_raw. SUB TOTAL Rp 9.660.000 correct."
          
          * **FAIL (row count)**: "Tabel detail memiliki 10 baris, namun periode coverage adalah 12 bulan. Row count tidak match dengan expected periode."
          
          * **FAIL (harga bulanan)**: "Tabel detail dengan 12 baris sesuai dengan coverage. Namun harga bulanan di tabel Rp 17.500.000 tidak match dengan GT.harga_satuan_raw Rp 17.839.350. Selisih: Rp 339.350 per bulan."
          
          * **FAIL (inconsistent harga)**: "Tabel detail dengan 12 baris. Harga bulanan tidak consistent: baris 1-6 menggunakan Rp 7.486.500, baris 7-12 menggunakan Rp 7.500.000. Expected: semua baris sama (Rp 7.486.500 per GT)."
          
          * **FAIL (SUB TOTAL)**: "Tabel detail: jumlah baris dan harga bulanan sesuai. Namun SUB TOTAL di summary Rp 215.000.000 tidak match dengan perhitungan (Rp 17.839.350 × 12 = Rp 214.072.200). Selisih: Rp 927.800."
          
          * **FAIL (multiple issues)**: "Tabel detail memiliki 10 baris (expected 12). Harga bulanan Rp 17.500.000 tidak match dengan GT Rp 17.839.350. SUB TOTAL Rp 175.000.000 juga tidak sesuai dengan perhitungan. Multiple inconsistencies detected."
          
          * **NOTE (gaps)**: "Tabel detail dengan 5 baris sesuai dengan coverage. Harga bulanan correct. Namun periode tidak sequential: Februari, April, Juni, Agustus, Oktober (ada gaps). Ini unusual pattern namun SUB TOTAL calculation correct."
        
        ─────────────────────────────────────────────────────────────────────────
        NOTES & GUIDELINES
        ─────────────────────────────────────────────────────────────────────────
        
        **PARSING NUMERIC VALUES (do in thinking block):**
        - Remove all formatting characters: "Rp", "Rp.", ".", ",", "-", spaces
        - Convert to pure integer for comparison
        - Examples:
          * "85.361.486" → 85361486
          * "Rp. 76.902.240,-" → 76902240
          * "76902240" → 76902240
        
        **PPN RATE AUTO-DETECTION (do in thinking block):**
        - Method 1: From explicit PPN amount
          * Calculate: (PPN / DPP) × 100
          * IF ≈ 11% → use 11%
          * IF ≈ 12% → use 12%
        - Method 2: From back-calculation
          * Try 11%: DPP = GRAND_TOTAL / 1.11
          * Try 12%: DPP = GRAND_TOTAL / 1.12
          * Compare with expected DPP
          * Choose best match
        - Always try 11% first, then 12%
        - Report detected rate in output
        
        **PARSING TERBILANG (do in thinking block):**
        - Indonesian number word mapping:
          * Units: satu=1, dua=2, ..., sembilan=9
          * Teens: sepuluh=10, sebelas=11, ..., sembilan belas=19
          * Tens: dua puluh=20, tiga puluh=30, ..., sembilan puluh=90
          * Hundreds: seratus=100, dua ratus=200, ..., sembilan ratus=900
          * Thousands: seribu=1000, dua ribu=2000, ..., sembilan ribu=9000
          * Multipliers: ribu=×1000, juta=×1000000, miliar=×1000000000
        - Handle "se-" prefix: seratus=100, seribu=1000, sejuta=1000000
        - Parse left-to-right, accumulate values
        - Example: "delapan puluh lima juta tiga ratus enam puluh satu ribu empat ratus delapan puluh enam"
          * "delapan puluh lima juta" → 85000000
          * "tiga ratus enam puluh satu ribu" → 361000
          * "empat ratus delapan puluh enam" → 486
          * Total: 85000000 + 361000 + 486 = 85361486
        
        **SEMANTIC MATCHING FOR JUDUL (do in thinking block):**
        - Extract keywords from both invoice judul and GT.judul_project
        - Common keywords: "Penyediaan", "Pengadaan", "Layanan", "Admin", "Tenaga", "Perangkat", nama instansi, lokasi
        - Ignore filler words: "untuk", "di", "pada", "periode"
        - Check keyword overlap
        - Threshold: at least 60-70% keyword match
        - Case insensitive comparison
        
        **EXACT MATCH VALIDATION (do in thinking block):**
        - Referensi NOPES/Kontrak: character-by-character, case-sensitive
        - Nomor Rekening: character-by-character, preserve format
        - All numeric amounts: integer comparison, zero tolerance
        - Periode dates: month + year match
        - NO rounding tolerance, NO fuzzy match for these fields
        
        **FUZZY MATCH VALIDATION (do in thinking block):**
        - Judul project: keyword matching (60-70%)
        - Nama bank: case-insensitive, ≥80% similarity
        - Kantor cabang: case-insensitive, ≥80% similarity
        - Atas nama: case-insensitive, ≥90% similarity
        
        **INVOICE FORMAT DETECTION (do in thinking block):**
        - Format 1 (Simple): NO tabel, hanya total amounts
          * Skip Stage 6
          * Focus Stage 1-5
        - Format 2 (Detailed Table): Ada tabel per-periode
          * Execute ALL stages including Stage 6
          * Validate tabel detail
        - Format 3 (Hybrid): Partial detail
          * Adaptive: validate apa yang ada
          * Skip apa yang tidak ada
        
        **DUPLICATE PAGE HANDLING:**
        - Extract from halaman 1 (first occurrence) only
        - Ignore halaman 2+ if they are duplicates
        - IF halaman 1 & 2 have different values → flag as document error
        
        **MULTI-PAGE HANDLING:**
        - Invoice bisa 1-3 pages
        - Halaman 1: usually complete information
        - Halaman 2+: usually duplicate atau lampiran
        - Extract dari ALL pages yang relevan
        - Prioritize halaman 1 untuk main extraction
        
        **COVERAGE TYPE IMPLICATIONS:**
        - FULL PERIOD: compare dengan GT.dpp_raw
        - PARTIAL PERIOD: calculate GT.harga_satuan_raw × bulan
        - SINGLE MONTH: compare dengan GT.harga_satuan_raw
        - This affects Stage 3 expected DPP calculation
        
        **ADAPTIVE STAGE EXECUTION:**
        - Stage 1-4: ALWAYS execute (mandatory)
        - Stage 5: execute IF detail rekening ada di invoice
        - Stage 6: execute IF tabel detail ada di invoice
        - SKIP = acceptable, not FAIL
        - Report SKIP status dengan clear reason
        
        ═════════════════════════════════════════════════════════════════════════════
        FINAL OUTPUT FORMAT (JSON ONLY)
        ═════════════════════════════════════════════════════════════════════════════
        
        {{
          "stage_1_header_referensi": {{
            "review": "Validasi judul project dan referensi NOPES/Kontrak",
            "keterangan": "[Narasi: Judul project... Referensi NOPES... Invoice nomor... tanggal... perusahaan...]"
          }},
          
          "stage_2_periode_coverage": {{
            "review": "Validasi periode coverage",
            "keterangan": "[Narasi: Periode range... Coverage type (full/partial/single)... Match vs GT...]"
          }},
          
          "stage_3_total_amount": {{
            "review": "Validasi DPP, PPN, dan GRAND TOTAL",
            "keterangan": "[Narasi: DPP vs GT... PPN rate (11% or 12%)... PPN calculation... GRAND TOTAL... Coverage context...]"
          }},
          
          "stage_4_terbilang": {{
            "review": "Validasi consistency terbilang dengan GRAND TOTAL",
            "keterangan": "[Narasi: Terbilang text (shortened)... Parsed value... Match vs GRAND TOTAL... atau FAIL reason...]"
          }},
          
          "stage_5_detail_rekening": {{
            "review": "Validasi detail rekening bank (optional, adaptive)",
            "keterangan": "[Narasi: Bank... Cabang... Nomor Rekening... Atas Nama... Match vs GT... atau SKIP reason...]"
          }},
          
          "stage_6_tabel_detail": {{
            "review": "Validasi tabel detail per-periode (optional, adaptive)",
            "keterangan": "[Narasi: Jumlah baris... Harga bulanan... SUB TOTAL calculation... Consistency... atau SKIP reason...]"
          }}
        }}
        
        ═════════════════════════════════════════════════════════════════════════════
        CRITICAL RULES
        ═════════════════════════════════════════════════════════════════════════════
        
        1. **ZERO HALLUCINATION:**
           - Jika tidak ditemukan → catat sebagai missing atau SKIP
           - Jangan guess atau assume nilai
           - Jangan fabricate data
           - Hanya report apa yang benar-benar terlihat di dokumen
        
        2. **ZERO TOLERANCE (EXACT MATCH):**
           - Semua numeric amounts: exact integer match (no rounding)
           - Referensi NOPES/Kontrak: character-by-character match
           - Nomor Rekening: exact format match
           - Periode dates: month + year exact match
           - Terbilang: must match GRAND TOTAL exactly
        
        3. **PPN AUTO-DETECTION (11% or 12%):**
           - Always try 11% first
           - IF not match → try 12%
           - Report detected rate in output
           - IF both not match → report both attempts
           - PPN calculation: exact integer result (round if needed)
        
        4. **DYNAMIC CALCULATION (Coverage-based):**
           - Expected DPP depends on coverage type:
             * FULL → GT.dpp_raw
             * PARTIAL → GT.harga_satuan_raw × bulan
             * SINGLE → GT.harga_satuan_raw
           - Always mention coverage type in output
           - Formula: GRAND TOTAL = DPP + PPN (where PPN = DPP × 11% or 12%)
        
        5. **TERBILANG IS MANDATORY:**
           - Must exist in invoice
           - Must be parseable
           - Must match GRAND TOTAL exactly
           - IF any condition fails → FAIL (not SKIP)
        
        6. **ADAPTIVE STAGES (Unified Approach):**
           - Stage 1-4: ALWAYS execute
           - Stage 5 (rekening): SKIP if not found in invoice
           - Stage 6 (tabel): SKIP if not found in invoice
           - Validate apa yang ada, SKIP apa yang tidak ada
           - SKIP ≠ FAIL (it's acceptable)
        
        7. **OUTPUT NARASI STYLE (NO "Sudah benar"/"Ada yang salah"):**
           - Langsung ke substansi tanpa prefix judgement
           - Format: "[Aspek] [hasil] [nilai konkrit] [reasoning singkat]"
           - Setiap aspek yang divalidasi HARUS dinarasikan hasilnya
           - Jika match: sebutkan nilai dan context
           - Jika tidak match: sebutkan kedua nilai, selisih, reasoning
           - Keep concise (max 3-4 kalimat per stage)
        
        8. **CHAIN OF THOUGHT (COT) IN THINKING BLOCK:**
           - All parsing: numeric, terbilang, periode → dalam thinking block
           - All calculations: PPN rate detection, expected DPP, differences → dalam thinking block
           - All comparisons dan matching logic → dalam thinking block
           - Output keterangan: hanya hasil akhir dan insight
           - Jangan expose step-by-step process di keterangan
        
        9. **NUMERIC FORMAT IN OUTPUT:**
           - Gunakan titik sebagai thousand separator: "85.361.486"
           - Gunakan prefix "Rp" atau "Rp." untuk mata uang
           - Sebutkan nilai konkrit, bukan placeholder
           - Untuk selisih: "Rp 1.902.240 (2,47%)"
           - Untuk PPN rate: "PPN 11%" atau "PPN 12%"
        
        10. **SEMANTIC MATCHING FOR JUDUL:**
            - Case insensitive
            - Keyword matching (60-70% threshold)
            - Ignore filler words
            - Focus on content keywords
            - Example: "Penyediaan Admin untuk Polda Kalsel Periode..." matches "Penyediaan Admin untuk Polda Kalsel"
        
        11. **EXACT MATCHING FOR REFERENSI:**
            - Character-by-character comparison
            - Case-sensitive (usually uppercase)
            - Preserve format: dots, slashes, dashes
            - NO fuzzy match, NO tolerance
            - Example: "C.TEL.1940/YN.000/TR4-W300/2024" must match exactly
        
        12. **FUZZY MATCHING FOR REKENING:**
            - Nama bank: ≥80% similarity, case-insensitive
            - Kantor cabang: ≥80% similarity, case-insensitive
            - Atas nama: ≥90% similarity, case-insensitive
            - Nomor rekening: EXACT match (no fuzzy)
        
        13. **TABEL VALIDATION (Format 2):**
            - Validate row count = periode count
            - Validate harga bulanan = GT.harga_satuan_raw (each row)
            - Validate SUB TOTAL = harga_bulanan × row_count
            - Check consistency: all rows same harga
            - Check sequence: periode sequential or gaps
        
        14. **DUPLICATE PAGE HANDLING:**
            - Extract from halaman 1 (first occurrence)
            - Ignore halaman 2+ if duplicate
            - IF values differ between pages → flag as document error
        
        15. **CONTEXT PRESERVATION IN OUTPUT:**
            - Always mention invoice nomor dan tanggal (extracted)
            - Always mention perusahaan mitra (extracted)
            - Always mention coverage type (full/partial/single)
            - Always mention PPN rate detected (11% or 12%)
            - Provide full context untuk transparency
        
        16. **VALIDATION SEQUENCE:**
            - Execute stages in order: 1 → 2 → 3 → 4 → 5 (if applicable) → 6 (if applicable)
            - Don't short-circuit: continue all stages even if early failures
            - Report all results regardless of dependencies
            - Stage 3 uses results from Stage 2 (coverage type)
        
        17. **MISSING DATA HANDLING:**
            - Judul missing: report missing, continue other validations
            - Referensi missing: report missing, continue
            - Periode missing: report missing, assume full period for Stage 3
            - DPP/PPN missing: report missing, cannot validate calculations
            - Terbilang missing: FAIL (mandatory field)
            - Rekening missing: SKIP (optional field)
            - Tabel missing: SKIP (optional field)
        
        18. **JSON OUTPUT COMPLIANCE:**
            - NO text outside JSON structure
            - NO markdown code blocks (```json ... ```)
            - NO explanatory text before/after JSON
            - Valid JSON format: proper escaping, no trailing commas
            - All 6 stage keys must be present (even if SKIP)
        
        19. **CONCISE KETERANGAN (Max 3-4 kalimat per stage):**
            - Stage 1: Judul result + Referensi result + Context (nomor, tanggal, perusahaan)
            - Stage 2: Periode range + Coverage type + Match result
            - Stage 3: DPP + PPN rate + PPN calc + GRAND TOTAL + Coverage context
            - Stage 4: Terbilang (short text) + Parsed value + Match result
            - Stage 5: Bank + Cabang + Rekening + Atas Nama + Match result (or SKIP)
            - Stage 6: Row count + Harga bulanan + SUB TOTAL + Consistency (or SKIP)
            - Fokus pada insight, bukan process detail
        
        20. **FRIENDLY & PROFESSIONAL TONE:**
            - Avoid harsh language
            - Provide constructive feedback with clear reasons
            - Use neutral framing for failures
            - Maintain professional but friendly tone
            - Example: "tidak sesuai" instead of "salah total"
            - Example: "Selisih: Rp X (Y%)" for transparency
        
        21. **INVOICE CONTEXT:**
            - Invoice adalah tagihan final (harga sudah include PPN)
            - Terbilang refers to GRAND TOTAL (not DPP)
            - Format sangat beragam: simple, detailed table, hybrid
            - Adaptive validation: handle all formats dengan unified approach
            - Detail rekening dan tabel: optional (not all invoices have them)
        
        22. **PRIORITY EQUAL:**
            - Semua stages punya priority sama (no hierarchy)
            - FAIL di any stage = issue yang perlu attention
            - Report all stages dengan equal importance
            - Don't prioritize total amounts over other fields
        
        23. **COMPARISON PRIORITY:**
            - Referensi: nomor_surat_utama (primary), nomor_surat_lainnya (secondary)
            - DPP: based on coverage type (GT.dpp_raw or calculated)
            - Terbilang: compare dengan GRAND TOTAL (include PPN)
            - Detail rekening: compare dengan GT.detail_rekening (if available)
            - Tabel: validate internal consistency + GT.harga_satuan_raw
        
        24. **THINKING BLOCK USAGE:**
            - Parse numeric values dalam thinking block
            - Parse terbilang dalam thinking block
            - Detect PPN rate dalam thinking block
            - Calculate expected values dalam thinking block
            - Calculate differences dalam thinking block
            - Determine semantic matching dalam thinking block
            - Extract keywords dalam thinking block
            - Output keterangan: only final results and insights
        
        25. **EXACT MATCH FIELDS (Zero Tolerance):**
            - Referensi NOPES/Kontrak
            - Nomor Rekening
            - All numeric amounts (DPP, PPN, GRAND TOTAL)
            - Terbilang parsed value
            - Periode dates (month + year)
            - SUB TOTAL calculation (dalam tabel)
        
        ═════════════════════════════════════════════════════════════════════════════
        RETURN FINAL JSON ANSWER ONLY - NO ADDITIONAL TEXT
        ═════════════════════════════════════════════════════════════════════════════
        """,
}


def get_prompt_template(task: str, file_type: str) -> str:
    """
    Get prompt template berdasarkan jenis file

    Args:
        task (str): Tugas yang akan dijalankan (EXTRACT/VALIDATION)
        file_type (str): Jenis file (KB, BAUT, NPK, dll)

    Returns:
        str: Prompt template atau None jika tidak ditemukan
    """

    if task == "EXTRACT":
        file_type_upper = file_type.upper()
        return EXTRACTION_PROMPT_TEMPLATES.get(file_type_upper)
    else:
        file_type_upper = file_type.upper()
        return VALIDATION_PROMPT_TEMPLATES.get(file_type_upper)


def get_supported_file_types(task: str) -> list:
    """
    Get list of supported file types

    Args:
        task (str): Tugas yang akan dijalankan (EXTRACT/VALIDATION)

    Returns:
        list: List of supported file types
    """
    if task == "EXTRACT":
        return list(EXTRACTION_PROMPT_TEMPLATES.keys())
    else:
        return list(VALIDATION_PROMPT_TEMPLATES.keys())
