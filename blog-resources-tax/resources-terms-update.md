# Resources

Order:

1. Update (types)
2. Rename (topics)
3. Merge (types, topics)
4. Remove (all)

## Types

### Update (Setup parent > child)

```csv
taxonomy,terms
content-type,"News & Updates > Press Release, News"
content-type,"Briefs > Executive Brief, Industry Brief, Product Brief, Service Brief, Solution Brief, Technical Brief, Partner Solution Brief"
content-type,"Success Stories > Case Study, Customer Testimonial Video, Case Study Video, Partner Success Profile, Customer Story"
content-type,"eBooks & Reports > eBook, Whitepaper, Article, Analyst Report, Infographic"
content-type,"Video > Demo, Product/Service Overview, Industry, Interview"
```

### Merge

```csv
taxonomy,from_terms,to_term
content-type,Report,Analyst Report
```

#### Remove

Delete terms

```csv
taxonomy,term
content-type,Other
content-type,Podcast
content-type,Blog
content-type,Webinar
```

## Products

### Remove

Delete terms

```csv
taxonomy,term
products,Boomi MCS
products,Partner Solutions
products,Spaces
```

## Topics

### Rename

```csv
taxonomy,old_term,new_name
topics,Cloud migration,Cloud
topics,Profile/Q&A,Industry Expert Interviews
```

### Merge

```csv
taxonomy,from_terms,to_term
topics,AI Governance,Artificial Intelligence (AI)
```

### Remove

Delete terms

```csv
taxonomy,term
topics,API management
topics,APJ
topics,Club Utilisateurs
```
