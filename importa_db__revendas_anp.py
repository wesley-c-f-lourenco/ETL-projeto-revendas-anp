"""
importa_db_revendas_anp.py

Script ETL (Extract, Transform, Load) para importar o CSV
de cadastro de revendas de GLP da ANP para o SQL Server.

Bibliotecas necessarias (instalar uma vez):
    pip install pandas pyodbc

Para conectar ao SQL Server voce tambem precisa do driver ODBC:
    - Windows: baixe o "ODBC Driver 17 for SQL Server" da Microsoft.
"""

import configparser
import sys
from datetime import datetime

import pandas as pd
import pyodbc


# ==============================================================
# PARTE 1: Leitura das configuracoes
# ==============================================================
config = configparser.ConfigParser()
lidos = config.read('config.ini')

if not lidos:
    print("ERRO: config.ini nao encontrado. Execute na pasta do projeto.")
    sys.exit(1)

DB_SERVER   = config['database']['server']
DB_NAME     = config['database']['name']
DB_SCHEMA   = config['database']['schema']
DB_TABLE    = config['database']['table']
CSV_URL     = config['csv']['url']


# ==============================================================
# PARTE 2: EXTRACT - leitura do CSV
# ==============================================================
# dtype=object evita o novo StringDtype do pandas 3.0,
# garantindo compatibilidade com pyodbc.

print(f"[1/4] Lendo CSV: {CSV_URL}")
print("      Isso pode levar alguns segundos na primeira vez...")

try:
    df = pd.read_csv(
        CSV_URL,
        encoding='latin1',
        sep=None,
        engine='python',
        dtype=object  # usa object em vez de str para compatibilidade
    )
except Exception as e:
    print(f"ERRO ao ler o CSV: {e}")
    sys.exit(1)

print(f"      {len(df)} linhas carregadas, {len(df.columns)} colunas.")


# ==============================================================
# PARTE 3: TRANSFORM - limpeza e ajuste dos dados
# ==============================================================
print("[2/4] Transformando dados...")

# --- Funcao auxiliar para converter nulos ---
# pandas 3.0 usa pd.NA para nulos em strings. pyodbc so entende None.
def para_none(val):
    """Converte pd.NA, np.nan e similares para None do Python."""
    if val is None:
        return None
    try:
        if pd.isna(val):
            return None
    except (TypeError, ValueError):
        pass
    return val

# --- CNPJ: zfill(14) para preservar zeros a esquerda ---
df['CNPJ'] = df['CNPJ'].apply(
    lambda x: str(x).strip().zfill(14) if para_none(x) is not None else None
)

# --- CEP: zfill(8) ---
df['CEP'] = df['CEP'].apply(
    lambda x: str(x).strip().zfill(8) if para_none(x) is not None else None
)

# --- CODIGOISIMP: converter para int ou None ---
def to_int_or_none(value):
    v = para_none(value)
    if v is None:
        return None
    try:
        return int(float(str(v).strip()))
    except (ValueError, TypeError):
        return None

df['CODIGOISIMP'] = df['CODIGOISIMP'].apply(to_int_or_none)

# --- Datas: converter de DD/MM/YYYY para datetime.date ---
def parse_data_br(valor):
    v = para_none(valor)
    if v is None:
        return None
    try:
        return datetime.strptime(str(v).strip(), '%d/%m/%Y').date()
    except ValueError:
        return None

df['DATAPUBLICACAO'] = df['DATAPUBLICACAO'].apply(parse_data_br)
df['DATAVINCULACAO'] = df['DATAVINCULACAO'].apply(parse_data_br)

# --- Strings: limpar espacos e converter nulos para None ---
colunas_texto = ['AUTORIZACAO', 'RAZAOSOCIAL', 'ENDERECO',
                 'COMPLEMENTO', 'BAIRRO', 'UF', 'MUNICIPIO',
                 'DISTRIBUIDORA', 'CLASSE']

for col in colunas_texto:
    df[col] = df[col].apply(
        lambda x: str(x).strip() if para_none(x) is not None else None
    )

print("      Transformacao concluida.")


# ==============================================================
# PARTE 4: LOAD - conexao e insercao no banco
# ==============================================================
print("[3/4] Conectando ao banco de dados...")

# Trusted_Connection=yes usa o login do Windows (sem usuario/senha)
conn_str = (
    f"DRIVER={{ODBC Driver 17 for SQL Server}};"
    f"SERVER={DB_SERVER};"
    f"DATABASE={DB_NAME};"
    f"Trusted_Connection=yes;"
    f"TrustServerCertificate=yes;"
)

try:
    conn = pyodbc.connect(conn_str)
    cursor = conn.cursor()
    print("      Conexao estabelecida.")
except pyodbc.Error as e:
    print(f"ERRO ao conectar: {e}")
    sys.exit(1)

insert_sql = f"""
    INSERT INTO [{DB_SCHEMA}].[{DB_TABLE}]
        (CODIGOISIMP, AUTORIZACAO, DATAPUBLICACAO, RAZAOSOCIAL, CNPJ,
         ENDERECO, COMPLEMENTO, BAIRRO, CEP, UF, MUNICIPIO,
         DISTRIBUIDORA, DATAVINCULACAO, CLASSE)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
"""

COLUNAS_INSERT = [
    'CODIGOISIMP', 'AUTORIZACAO', 'DATAPUBLICACAO', 'RAZAOSOCIAL', 'CNPJ',
    'ENDERECO', 'COMPLEMENTO', 'BAIRRO', 'CEP', 'UF', 'MUNICIPIO',
    'DISTRIBUIDORA', 'DATAVINCULACAO', 'CLASSE'
]

print(f"[4/4] Inserindo {len(df)} registros em lotes de 500...")

BATCH_SIZE = 500
total_inserido = 0

for inicio in range(0, len(df), BATCH_SIZE):
    lote = df.iloc[inicio : inicio + BATCH_SIZE]

    # para_none() garante que todo nulo vira None (SQL NULL).
    # Isso e essencial no pandas 3.0 que usa pd.NA em vez de np.nan.
    linhas = [
        tuple(para_none(row[col]) for col in COLUNAS_INSERT)
        for _, row in lote.iterrows()
    ]

    try:
        cursor.executemany(insert_sql, linhas)
        conn.commit()
    except pyodbc.Error as e:
        conn.rollback()
        print(f"ERRO no lote {inicio}-{inicio + BATCH_SIZE}: {e}")
        continue

    total_inserido += len(lote)
    print(f"      {total_inserido}/{len(df)} registros inseridos...", end='\r')

cursor.close()
conn.close()

print(f"\nConcluido! {total_inserido} registros importados para [{DB_SCHEMA}].[{DB_TABLE}].")
