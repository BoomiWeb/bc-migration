## Resources

Order:

1. Setup parent > child (types)
2. Rename (topics)
3. Merge (types, topics)
4. Remove (all)

### Types

#### Setup parent > child

```
content-type,"News & Updates > Press Release, News"
content-type,"Briefs > Executive Brief, Industry Brief, Product Brief, Service Brief, Solution Brief, Technical Brief, Partner Solution Brief"
content-type,"Success Stories > Case Study, Customer Testimonial Video, Case Study Video, Partner Success Profile, Customer Story"
content-type,"eBooks & Reports > eBook, Whitepaper, Article, Analyst Report, Infographic"
content-type,"Video > Demo, Product/Service Overview, Industry, Interview"
```

### Merge

```
taxonomy,from_terms,to_term
content-type,Report,Analyst Report
```

#### Remove

Delete terms

```
taxonomy,term
content-type,Other
content-type,Podcast
content-type,Blog
content-type,Webinar
```

### Products

#### Remove

Delete terms

```
taxonomy,term
products,Boomi MCS
products,Partner Solutions
products,Spaces
```

### Topics

#### Rename

```
taxonomy,old_term,new_name
topics,Cloud migration,Cloud
topics,Profile/Q&A,Industry Expert Interviews
```

#### Merge

```
taxonomy,from_terms,to_term
topics,AI Governance,Artificial Intelligence (AI)
```

#### Remove

Delete terms

```
taxonomy,term
topics,API management
topics,APJ
topics,Club Utilisateurs
```

---

Current delete.csv file in examples folder:

```
taxonomy,term
blog_posts,Sales/Marketing Systems
blog_posts,Emerging Technologies
blog_posts,IoT
blog_posts,Blockchain
blog_posts,Product Announcements
blog_posts,Boomi Platform Tips
blog_posts,Boomi Labs
blog_posts,Boomi Community
blog_posts,Boomi Events
blog_posts,Awards
content-type,Other
content-type,Podcast
content-type,Blog
content-type,Webinar
products,Boomi MCS
products,Partner Solutions
products,Spaces
topics,API management
topics,APJ
topics,Club Utilisateurs
```