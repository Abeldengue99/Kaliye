import re
import os

def parse_sql_to_mermaid(sql_content):
    tables = {}
    relationships = []

    # Regex patterns
    create_table_pattern = re.compile(r"CREATE TABLE IF NOT EXISTS `(\w+)` \((.*?)\) ENGINE=", re.DOTALL)
    alter_table_fk_pattern = re.compile(r"ALTER TABLE `(\w+)`.*?FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`(\w+)`\)", re.DOTALL | re.IGNORECASE)
    
    # Parse CREATE TABLE blocks
    for match in create_table_pattern.finditer(sql_content):
        table_name = match.group(1)
        body = match.group(2)
        
        columns = []
        pk = None
        
        # Parse lines in the body
        lines = body.split('\n')
        for line in lines:
            line = line.strip()
            if not line:
                continue
            
            # Extract column
            col_match = re.match(r"`(\w+)` (\w+(?:\(.*?\))?)", line)
            if col_match:
                col_name = col_match.group(1)
                col_type = col_match.group(2)
                columns.append((col_name, col_type))
            
            # Extract PK
            if line.startswith("PRIMARY KEY"):
                pk_match = re.search(r"PRIMARY KEY \(`(\w+)`\)", line)
                if pk_match:
                    pk = pk_match.group(1)

        tables[table_name] = {
            "columns": columns,
            "pk": pk
        }

    # Parse ALTER TABLE ADD CONSTRAINT (Foreign Keys)
    # The dump has multiple ADD CONSTRAINT in one ALTER TABLE sometimes, or separate statements.
    # The regex needs to handle specific format seen in the dump.
    
    # The dump uses:
    # ALTER TABLE `table`
    #   ADD CONSTRAINT `name` FOREIGN KEY (`col`) REFERENCES `ref_table` (`ref_col`) ...;
    
    # We can rely on capturing the whole ALTER TABLE block or just finding all FK definitions if they are standardized.
    # Given the dump, let's look for "ALTER TABLE `X`" and then look for FKs within it until ";"
    
    # Actually, simpler: finding all "FOREIGN KEY (`col`) REFERENCES `table` (`col`)" and associating with the table in the preceding "ALTER TABLE"
    
    # Let's split by statement to be safe
    statements = sql_content.split(';')
    
    current_table = None
    
    for statement in statements:
        statement = statement.strip()
        if statement.startswith("ALTER TABLE"):
            # Extract table name
            table_match = re.match(r"ALTER TABLE `(\w+)`", statement)
            if table_match:
                table_name = table_match.group(1)
                # Find all FKs in this statement
                fk_matches = re.finditer(r"FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`(\w+)`\)", statement)
                for fk in fk_matches:
                    col = fk.group(1)
                    ref_table = fk.group(2)
                    ref_col = fk.group(3)
                    relationships.append((table_name, col, ref_table, ref_col))
        
        # Also check for FKs defined inside CREATE TABLE (rare in this dump but good practice)
        # Note: The provided dump has keys defined at the end of CREATE TABLE but strictly as KEY or UNIQUE KEY, 
        # FKs seem to be in ALTER TABLE at the end.
        
    # Generate Mermaid syntax
    mermaid_lines = ["erDiagram"]
    
    # Add tables
    for table_name, data in tables.items():
        mermaid_lines.append(f"    {table_name} {{")
        for col_name, col_type in data['columns']:
            # Mermaid types simplified
            ctype = col_type.split('(')[0] if '(' in col_type else col_type
            comment = "PK" if col_name == data['pk'] else ""
            # FK comment could be added if we tracked which cols are FKs in the dictionary, but relationships handle the lines.
            
            mermaid_lines.append(f"        {ctype} {col_name} {comment}")
        mermaid_lines.append(f"    }}")
    
    # Add relationships
    for table, col, ref_table, ref_col in relationships:
        # Assuming 1 to many mostly, referencing table is 'many' side usually (contains FK)
        # ref_table ||--o{ table : "has"
        mermaid_lines.append(f"    {ref_table} ||--o{{ {table} : \"{col}\"")
        
    return "\n".join(mermaid_lines)

if __name__ == "__main__":
    script_dir = os.path.dirname(os.path.abspath(__file__))
    project_root = os.path.dirname(os.path.dirname(script_dir))
    input_path = os.path.join(project_root, 'docs', 'db_schema_dump.sql')
    output_path = os.path.join(project_root, 'docs', 'database_diagram.md')
    
    try:
        try:
            with open(input_path, 'r', encoding='utf-8') as f:
                content = f.read()
        except UnicodeError:
            with open(input_path, 'r', encoding='utf-16') as f:
                content = f.read()
            
        mermaid_diagram = parse_sql_to_mermaid(content)
        
        markdown_content = f"""# Database Schema Diagram

```mermaid
{mermaid_diagram}
```
"""
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(markdown_content)
            
        print(f"Successfully generated database diagram at {output_path}")
        
    except Exception as e:
        print(f"Error: {e}")
