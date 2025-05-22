## Blog

Order:

1. Rename
2. Merge
3. Remove
4. Check*

### Industries

#### Merge

Pharmaceuticals > Life Sciences (move into LS aka "Rollup")

```
taxonomy,from_terms,to_term
blog_posts,Pharmaceuticals,Life Sciences
```

#### Migrate

Life Sciences - blog_posts > industries (Change taxonomy)

```
term_name,from_taxonomy,to_taxonomy
Life Sciences,blog_posts,industries
```

### Products

#### Rename

Rename terms

```
taxonomy,old_term,new_name
products,Boomi DataHub,DataHub
products,Boomi Flow,Flow
products, Event-Driven Architecture,Event Streams
products,Embedded Use,Embedded
```

#### Merge

"Rollup" industries terms

```
taxonomy,from_terms,to_term
products,Data Catalog and Preparation|Data & Application Integration|Master Data Management,Data Management
products,EDI (Electronic Data Interchange),B2B/EDI Management
products,B2B Integration|CRM Integration|ERP Integration|Enterprise Integration|On-Premises Application Integration,Integration
products,OEM,Embedded
```

### Topics

#### Rename

Rename terms

```
taxonomy,old_term,new_name
topics,AI + Machine Learning,Artificial Intelligence (AI)
topics,Profile / Q&A,Industry Expert Interviews
```

#### Merge

"Rollup" industries terms

```
taxonomy,from_terms,to_term
topics,Automation,Workflow Automation
topics,Cloud Management,Cloud
```

### Remove

Delete terms

```
taxonomy,term
toipcs,Sales/Marketing Systems
toipcs,Emerging Technologies
toipcs,IoT
toipcs,Blockchain
toipcs,Product Announcements
toipcs,Boomi Platform Tips
toipcs,Boomi Labs
toipcs,Boomi Community
toipcs,Boomi Events
toipcs,Awards
```